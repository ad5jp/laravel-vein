<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\FileUpload;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestFile;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SecurityTest extends TestCase
{
    private function signinUrl(): string
    {
        return '/'.config('vein.admin_uri').'/signin';
    }

    private function admin(): TestUser
    {
        return TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);
    }

    public function test_mime_type_に属性を閉じる文字列を入れても壊れない(): void
    {
        Storage::fake(config('vein.upload_disk'));
        Storage::disk(config('vein.upload_disk'))->put('vein-upload/x.png', $this->pngBytes());

        // mime_type は hidden input 由来なので、こういう値が DB に入りうる
        $file = TestFile::create([
            'file_name' => 'x.png',
            'file_path' => 'vein-upload/x.png',
            'mime_type' => 'image/png;base64,x" onerror="alert(1)',
            'file_size' => 1,
        ]);

        $entry = TestEntry::create(['title' => '親']);
        $record = new TestRecord(['caption' => '1 枚目', 'test_file_id' => $file->id]);
        $entry->records()->save($record);

        $html = (new FileUpload(key: 'file'))->renderInline($record->fresh());

        $this->assertStringNotContainsString('onerror=', $html);
    }

    public function test_ログインを六回失敗すると弾かれる(): void
    {
        $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->post($this->signinUrl(), ['email' => 'admin@example.com', 'password' => 'wrong'])
                ->assertRedirect();
        }

        $this->post($this->signinUrl(), ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_ログアウトでセッションが破棄される(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['vein_probe' => '残ってはいけない値'])
            ->post('/'.config('vein.admin_uri').'/signout')
            ->assertRedirect(route('vein.signin'));

        // invalidate() はセッションの中身を捨てて ID を振り直す
        $this->assertNull(session('vein_probe'));
        $this->assertGuest();
    }

    public function test_ログイン失敗後の_htm_l_にパスワードが出ない(): void
    {
        $this->admin();

        $this->post($this->signinUrl(), [
            'email' => 'admin@example.com',
            'password' => 'ひみつのあいことば',
        ])->assertRedirect();

        $html = $this->get($this->signinUrl())->getContent();

        $this->assertStringNotContainsString('ひみつのあいことば', (string) $html);
        $this->assertStringContainsString('admin@example.com', (string) $html);
    }
}
