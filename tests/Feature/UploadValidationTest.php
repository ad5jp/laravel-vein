<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\FileUpload;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * アップロードされたファイルに検証がかかること。
 *
 * ホワイトリストが無いと、内容が HTML / SVG と判定されるファイルがその拡張子で
 * 保存される。公開ディスクなら同一オリジンに任意の HTML を置けることになる。
 */
class UploadValidationTest extends TestCase
{
    private function url(): string
    {
        return '/'.config('vein.admin_uri').'/upload';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('vein.temporary_disk'));
        Storage::fake(config('vein.upload_disk'));

        $this->actingAs(TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]));
    }

    public function test_許可された画像は通る(): void
    {
        // fake()->image() は GD を要求するので、実データを渡す
        $file = UploadedFile::fake()->createWithContent('photo.png', $this->pngBytes());

        $this->post($this->url(), ['upload' => $file])
            ->assertOk()
            ->assertJsonStructure(['preview', 'value']);
    }

    public function test_許可外の拡張子は弾かれる(): void
    {
        $file = UploadedFile::fake()->createWithContent('evil.html', '<script>alert(1)</script>');

        $this->post($this->url(), ['upload' => $file])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_svg_は既定では弾かれる(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svg);

        $this->post($this->url(), ['upload' => $file])->assertStatus(422);
    }

    public function test_ファイル無しで送っても_500_にならない(): void
    {
        $this->post($this->url(), [])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_上限を超えるサイズは弾かれる(): void
    {
        config()->set('vein.upload_max_kilobytes', 1);

        $file = UploadedFile::fake()->create('big.png', 100);

        $this->post($this->url(), ['upload' => $file])->assertStatus(422);
    }

    public function test_エラーにサーバー内部のパスが出ない(): void
    {
        $file = UploadedFile::fake()->createWithContent('evil.html', '<b>x</b>');

        $body = $this->post($this->url(), ['upload' => $file])->getContent();

        $this->assertStringNotContainsString('/app/', (string) $body);
        $this->assertStringNotContainsString('vendor/', (string) $body);
    }

    public function test_保存の直前にも中身を確かめる(): void
    {
        // 一時領域に、名前だけ png のテキストを置く（アップロード検証をすり抜けた想定）
        Storage::disk(config('vein.temporary_disk'))->put('vein-tmp/fake.png', '<script>alert(1)</script>');

        $entry = TestEntry::create(['title' => '親']);
        $record = new TestRecord(['caption' => '1 枚目']);
        $entry->records()->save($record);

        $payload = ['file' => json_encode([
            'tmp_path' => 'vein-tmp/fake.png',
            'file_name' => 'fake.png',
            'file_size' => 10,
            'mime_type' => 'image/png',
        ])];

        $this->expectException(Exception::class);

        (new FileUpload(key: 'file'))->beforeSave($record->fresh(), $payload);
    }

    public function test_許可する拡張子は要素ごとに変えられる(): void
    {
        $control = new FileUpload(key: 'file', extensions: ['pdf']);

        $this->assertSame(['pdf'], $control->extensions);
    }
}
