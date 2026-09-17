<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\FileUpload;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestFile;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

/**
 * 保存するバイト数は、置いた実体から取ること。
 *
 * ブラウザから送られてくる JSON は hidden input 由来で、開発者ツールから
 * 書き換えられる。同じ JSON に入っている mime_type は「信用しない」として
 * 中身から判定し直しているのに、file_size だけがその扱いから漏れていた。
 *
 * いま file_size を読む箇所は無いので壊れてはいない。あとから容量を集計したり
 * 重い画像を洗い出したりしたときに、静かに間違った数字が出る。
 */
class FileSizeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('vein.upload_disk'));
        Storage::fake(config('vein.temporary_disk'));
        Storage::disk(config('vein.temporary_disk'))->put('vein-tmp/new.png', $this->pngBytes());
    }

    public function test_送られてきたバイト数ではなく実体のバイト数を保存する(): void
    {
        $entry = TestEntry::create(['title' => '親']);
        $record = new TestRecord(['caption' => '1 枚目']);
        $entry->records()->save($record);

        $payload = ['file' => json_encode([
            'tmp_path' => 'vein-tmp/new.png',
            'file_name' => 'new.png',
            'mime_type' => 'image/png',
            // 実体と食い違う値を送る（書き換えられた想定）
            'file_size' => 999999,
        ])];

        $record = (new FileUpload(key: 'file'))->beforeSave($record->fresh(), $payload);
        $record->save();

        $file = TestFile::find($record->test_file_id);

        $this->assertNotNull($file);
        $this->assertSame(strlen($this->pngBytes()), $file->getFileSize());
        $this->assertNotSame(999999, $file->getFileSize());
    }
}
