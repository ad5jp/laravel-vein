<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Node\NodeDeleter;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestFile;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\Fixtures\TestSoftEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Node を削除したとき、子レコードとファイルが残らないこと。
 *
 * 画面からは消えるので、残っていても気づけない。掲載を取り下げた内容の画像が
 * URL を直接叩けば見える、という形で効く。
 */
class NodeDeletionTest extends TestCase
{
    private string $path = 'vein-upload/sample.png';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('vein.upload_disk'));
        Storage::disk(config('vein.upload_disk'))->put($this->path, 'dummy');
    }

    private function fileRecord(): TestFile
    {
        return TestFile::create([
            'file_name' => 'sample.png',
            'file_path' => $this->path,
            'mime_type' => 'image/png',
            'file_size' => 5,
        ]);
    }

    private function seedChildren(Model $entry): TestFile
    {
        $file = $this->fileRecord();

        $entry->records()->save(new TestRecord(['caption' => '1 枚目', 'test_file_id' => $file->id]));
        $entry->records()->save(new TestRecord(['caption' => '2 枚目']));

        return $file;
    }

    public function test_物理削除で子レコードとファイルが消える(): void
    {
        $entry = TestEntry::create(['title' => '親']);
        $file = $this->seedChildren($entry);

        (new NodeDeleter)->delete($entry->fresh());

        $this->assertNull(TestEntry::find($entry->id));
        $this->assertSame(0, TestRecord::where('test_entry_id', $entry->id)->count());
        $this->assertNull(TestFile::find($file->id));
        Storage::disk(config('vein.upload_disk'))->assertMissing($this->path);
    }

    public function test_論理削除では既定で子レコードもファイルも残る(): void
    {
        $entry = TestSoftEntry::create(['title' => '親']);
        $file = $this->seedChildren($entry);

        (new NodeDeleter)->delete($entry->fresh());

        $this->assertNull(TestSoftEntry::find($entry->id));
        $this->assertNotNull(TestSoftEntry::withTrashed()->find($entry->id));
        $this->assertSame(2, TestRecord::where('test_entry_id', $entry->id)->count());
        $this->assertNotNull(TestFile::find($file->id));
        Storage::disk(config('vein.upload_disk'))->assertExists($this->path);
    }

    public function test_設定を有効にすると論理削除でも片付く(): void
    {
        config()->set('vein.cascade_on_soft_delete', true);

        $entry = TestSoftEntry::create(['title' => '親']);
        $file = $this->seedChildren($entry);

        (new NodeDeleter)->delete($entry->fresh());

        $this->assertNotNull(TestSoftEntry::withTrashed()->find($entry->id));
        $this->assertSame(0, TestRecord::where('test_entry_id', $entry->id)->count());
        $this->assertNull(TestFile::find($file->id));
        Storage::disk(config('vein.upload_disk'))->assertMissing($this->path);
    }

    public function test_管理画面からの削除でも子が残らない(): void
    {
        $admin = TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $entry = TestEntry::create(['title' => '親']);
        $file = $this->seedChildren($entry);

        $this->actingAs($admin)
            ->post(sprintf('/%s/test_entry/%s/delete', config('vein.admin_uri'), $entry->id))
            ->assertRedirect();

        $this->assertNull(TestEntry::find($entry->id));
        $this->assertSame(0, TestRecord::where('test_entry_id', $entry->id)->count());
        $this->assertNull(TestFile::find($file->id));
    }
}
