<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\FileUpload;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestFile;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * ファイルの差し替えで、DB とストレージが食い違わないこと。
 *
 * Storage はトランザクションの対象外なので、その場で消すとロールバックしても戻らない。
 * DB には旧ファイルのレコードがあるのに実体だけ無い、という状態になる。
 */
class FileTransactionTest extends TestCase
{
    private string $oldPath = 'vein-upload/old.png';

    private string $newPath = 'vein-upload/new.png';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('vein.upload_disk'));
        Storage::fake(config('vein.temporary_disk'));

        Storage::disk(config('vein.upload_disk'))->put($this->oldPath, '旧');
        Storage::disk(config('vein.temporary_disk'))->put('vein-tmp/new.png', '新');
    }

    private function recordWithFile(): TestRecord
    {
        $entry = TestEntry::create(['title' => '親']);

        $file = TestFile::create([
            'file_name' => 'old.png',
            'file_path' => $this->oldPath,
            'mime_type' => 'image/png',
            'file_size' => 3,
        ]);

        $record = new TestRecord(['caption' => '1 枚目', 'test_file_id' => $file->id]);
        $entry->records()->save($record);

        return $record->fresh();
    }

    private function newFilePayload(): array
    {
        return ['file' => json_encode([
            'tmp_path' => 'vein-tmp/new.png',
            'file_name' => 'new.png',
            'mime_type' => 'image/png',
            'file_size' => 3,
        ])];
    }

    public function test_保存できたら旧ファイルの実体が消える(): void
    {
        $record = $this->recordWithFile();

        DB::transaction(function () use ($record): void {
            (new FileUpload(key: 'file'))->beforeSave($record, $this->newFilePayload());
            $record->save();
        });

        $disk = Storage::disk(config('vein.upload_disk'));
        $disk->assertMissing($this->oldPath);
        $disk->assertExists($this->newPath);
    }

    public function test_保存に失敗したら旧ファイルの実体が残る(): void
    {
        $record = $this->recordWithFile();
        $oldFileId = $record->test_file_id;

        try {
            DB::transaction(function () use ($record): void {
                (new FileUpload(key: 'file'))->beforeSave($record, $this->newFilePayload());
                $record->save();

                throw new RuntimeException('後続の処理が失敗した');
            });
        } catch (RuntimeException) {
            // 握りつぶす
        }

        // DB は元に戻っている
        $this->assertNotNull(TestFile::find($oldFileId));
        $this->assertSame($oldFileId, $record->fresh()->test_file_id);

        // 実体も元に戻っていること
        Storage::disk(config('vein.upload_disk'))->assertExists($this->oldPath);
    }

    public function test_失敗したら新しく置いたファイルが残らない(): void
    {
        $record = $this->recordWithFile();

        try {
            DB::transaction(function () use ($record): void {
                (new FileUpload(key: 'file'))->beforeSave($record, $this->newFilePayload());
                $record->save();

                throw new RuntimeException('後続の処理が失敗した');
            });
        } catch (RuntimeException) {
            // 握りつぶす
        }

        Storage::disk(config('vein.upload_disk'))->assertMissing($this->newPath);
    }
}
