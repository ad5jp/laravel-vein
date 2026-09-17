<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\FileUpload;
use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Node\NodeDeleter;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestFile;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 外部キーがある環境で、消す順序が正しいこと。
 *
 * ファイルのレコードは複数の行から指されるので、指している行が全部消えるまで
 * 消せない。順序を間違えると外部キーに弾かれ、実体だけ先に消えて戻らない。
 *
 * 外部キーはテスト用スキーマ（TestCase）で張ってある。
 */
class DeletionOrderTest extends TestCase
{
    private function file(string $path): TestFile
    {
        Storage::disk(config('vein.upload_disk'))->put($path, 'dummy');

        return TestFile::create([
            'file_name' => basename($path),
            'file_path' => $path,
            'mime_type' => 'image/png',
            'file_size' => 5,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('vein.upload_disk'));
    }

    /** 親が直接持つファイルも、子が持つファイルも、外部キーに弾かれずに消える。 */
    public function test_親と子の両方がファイルを持っていても削除が通る(): void
    {
        $own = $this->file('vein-upload/own.png');
        $child = $this->file('vein-upload/child.png');

        $entry = TestEntry::create(['title' => '親', 'test_file_id' => $own->id]);
        $entry->records()->save(new TestRecord(['caption' => '1 枚目', 'test_file_id' => $child->id]));

        (new NodeDeleter)->delete($entry->fresh());

        $this->assertNull(TestEntry::find($entry->id));
        $this->assertSame(0, TestRecord::where('test_entry_id', $entry->id)->count());
        $this->assertNull(TestFile::find($own->id));
        $this->assertNull(TestFile::find($child->id));

        $disk = Storage::disk(config('vein.upload_disk'));
        $disk->assertMissing('vein-upload/own.png');
        $disk->assertMissing('vein-upload/child.png');
    }

    /** 途中で失敗したら、実体もレコードも元のまま。 */
    public function test_削除が失敗したら実体が残る(): void
    {
        $own = $this->file('vein-upload/own.png');
        $entry = TestEntry::create(['title' => '親', 'test_file_id' => $own->id]);

        // 親の削除を失敗させる
        TestEntry::deleting(static fn () => throw new \RuntimeException('わざと失敗'));

        try {
            (new NodeDeleter)->delete($entry->fresh());
            $this->fail('例外が飛ぶはず');
        } catch (\RuntimeException) {
            // 握りつぶす
        }

        TestEntry::flushEventListeners();

        $this->assertNotNull(TestEntry::find($entry->id));
        $this->assertNotNull(TestFile::find($own->id));
        Storage::disk(config('vein.upload_disk'))->assertExists('vein-upload/own.png');
    }

    /** 差し替えたら、旧ファイルのレコードも消える（実体だけでなく）。 */
    public function test_差し替えで旧ファイルのレコードが消える(): void
    {
        $old = $this->file('vein-upload/old.png');
        $record = TestRecord::create([
            'test_entry_id' => TestEntry::create(['title' => '親'])->id,
            'caption' => 'あ',
            'test_file_id' => $old->id,
        ]);

        Storage::disk(config('vein.temporary_disk'))->put('vein-tmp/new.png', $this->pngBytes());

        $payload = ['file' => json_encode([
            'tmp_path' => 'vein-tmp/new.png',
            'file_name' => 'new.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($this->pngBytes()),
        ])];

        $upload = new FileUpload(key: 'file');

        DB::transaction(function () use ($upload, $record, $payload): void {
            $record = $upload->beforeSave($record, $payload);
            $record->save();
            $upload->afterSave($record, $payload);
        });

        $this->assertNull(TestFile::find($old->id));
        $this->assertNotSame($old->id, $record->fresh()->test_file_id);
        Storage::disk(config('vein.upload_disk'))->assertMissing('vein-upload/old.png');
    }

    /** 画面から行を外したら、その行が持っていたファイルも残らない。 */
    public function test_行を外すとファイルも残らない(): void
    {
        $file = $this->file('vein-upload/removed.png');

        $entry = TestEntry::create(['title' => '親']);
        $row = new TestRecord(['caption' => '消される行', 'test_file_id' => $file->id]);
        $entry->records()->save($row);

        // 画面から行が無くなった状態で保存する。
        // 子行の保存は親のキーが要るので Records::applyAfterSave の側にある
        DB::transaction(function () use ($entry): void {
            (new Records(key: 'records', label: '子レコード'))
                ->afterSave($entry->fresh(), ['records' => []]);
        });

        $this->assertSame(0, TestRecord::where('test_entry_id', $entry->id)->count());
        $this->assertNull(TestFile::find($file->id));
        Storage::disk(config('vein.upload_disk'))->assertMissing('vein-upload/removed.png');
    }
}
