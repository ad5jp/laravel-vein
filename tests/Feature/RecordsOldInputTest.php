<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestFile;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

/**
 * 検証エラーで戻ったとき、Records の各行に入力が残ること。
 *
 * 行の中の欄は、描画したあとで name を records[0][file] に書き換えられる。
 * 描画の時点では自分のキーが file のままなので、old() は行の添字付きで引く必要がある。
 */
class RecordsOldInputTest extends TestCase
{
    private string $storedPath = 'vein/stored.png';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('vein.upload_disk'));
        Storage::fake(config('vein.temporary_disk'));

        Storage::disk(config('vein.upload_disk'))->put($this->storedPath, $this->pngBytes());
        Storage::disk(config('vein.temporary_disk'))->put('vein-tmp/new.png', $this->pngBytes());

        $this->app['request']->setLaravelSession($this->app['session.store']);
    }

    private function control(): Records
    {
        return new Records(key: 'records', label: '子レコード');
    }

    private function storedFile(): TestFile
    {
        return TestFile::create([
            'file_name' => 'stored.png',
            'file_path' => $this->storedPath,
            'mime_type' => 'image/png',
            'file_size' => strlen($this->pngBytes()),
        ]);
    }

    /** 検証エラーで戻ってきた直後の old を作る。 */
    private function withOldInput(array $input): void
    {
        $this->app['session.store']->flashInput($input);
    }

    public function test_保存済みの画像が残る(): void
    {
        $file = $this->storedFile();
        $this->withOldInput(['records' => [
            ['id' => '', 'caption' => '1 枚目', 'file' => (string) $file->id],
        ]]);

        $html = $this->control()->render(TestEntry::create(['title' => '親']));

        $this->assertStringContainsString(sprintf('name="records[0][file]" value="%s"', $file->id), $html);
    }

    public function test_アップロードしたばかりの画像が残る(): void
    {
        $json = json_encode([
            'tmp_path' => 'vein-tmp/new.png',
            'file_name' => 'new.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($this->pngBytes()),
        ]);
        $this->withOldInput(['records' => [
            ['id' => '', 'caption' => '1 枚目', 'file' => $json],
        ]]);

        $html = $this->control()->render(TestEntry::create(['title' => '親']));

        $this->assertStringContainsString(sprintf('name="records[0][file]" value="%s"', e($json)), $html);
    }

    public function test_画像はその行にだけ出る(): void
    {
        $file = $this->storedFile();
        $this->withOldInput(['records' => [
            ['id' => '', 'caption' => '1 枚目', 'file' => ''],
            ['id' => '', 'caption' => '2 枚目', 'file' => (string) $file->id],
        ]]);

        $html = $this->control()->render(TestEntry::create(['title' => '親']));

        $this->assertStringContainsString(sprintf('name="records[1][file]" value="%s"', $file->id), $html);
        // 「追加」の雛形も records[0] で描かれるので、ここで雛形に漏れていないことも見ている
        $this->assertStringNotContainsString(sprintf('name="records[0][file]" value="%s"', $file->id), $html);
    }

    public function test_親に同じ名前の欄があっても行の値が出る(): void
    {
        $this->withOldInput([
            'caption' => '親の値',
            'records' => [['id' => '', 'caption' => '行の値']],
        ]);

        $html = $this->control()->render(TestEntry::create(['title' => '親']));

        $this->assertStringContainsString('行の値', $html);
        $this->assertStringNotContainsString('親の値', $html);
    }
}
