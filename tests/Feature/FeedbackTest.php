<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

/**
 * 操作の結果が画面に出ること。
 *
 * サーバー側はメッセージを用意していたが、受け側が無かったため
 * 「保存を押しても同じ画面が再描画されるだけ」になっていた。
 */
class FeedbackTest extends TestCase
{
    private function admin(): TestUser
    {
        return TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);
    }

    private function adminUri(): string
    {
        return '/'.config('vein.admin_uri');
    }

    public function test_保存するとメッセージが出る(): void
    {
        $entry = TestEntry::create(['title' => '親']);

        $response = $this->actingAs($this->admin())
            ->post(sprintf('%s/test_entry/%s', $this->adminUri(), $entry->id), ['title' => '変えた']);

        $response->assertRedirect();
        $response->assertSessionHas('message.success', '保存しました');

        $this->followRedirects($response)->assertSeeText('保存しました');
    }

    public function test_削除するとメッセージが出る(): void
    {
        $entry = TestEntry::create(['title' => '親']);

        $response = $this->actingAs($this->admin())
            ->post(sprintf('%s/test_entry/%s/delete', $this->adminUri(), $entry->id));

        $response->assertSessionHas('message.success', '削除しました');
        $this->followRedirects($response)->assertSeeText('削除しました');
    }

    public function test_ログイン失敗が画面に出る(): void
    {
        $this->admin();

        $response = $this->from($this->adminUri().'/signin')
            ->post($this->adminUri().'/signin', [
                'email' => 'admin@example.com',
                'password' => 'wrong',
            ]);

        $this->followRedirects($response)
            ->assertSeeText('メールアドレスかパスワードが間違っています。');
    }

    public function test_ログアウトが画面に出る(): void
    {
        $response = $this->actingAs($this->admin())->post($this->adminUri().'/signout');

        $this->followRedirects($response)->assertSeeText('ログアウトしました');
    }

    public function test_アップロード欄に状態の置き場がある(): void
    {
        $entry = TestEntry::create(['title' => '親']);
        $entry->records()->save(new TestRecord(['caption' => '1 枚目']));

        $html = $this->actingAs($this->admin())
            ->get(sprintf('%s/test_entry/%s', $this->adminUri(), $entry->id))
            ->getContent();

        $this->assertStringContainsString('__uploader_status', (string) $html);
    }

    public function test_通信の成否を見てから_do_m_を触っている(): void
    {
        $entry = TestEntry::create(['title' => '親']);

        $html = (string) $this->actingAs($this->admin())
            ->get(sprintf('%s/test_entry/%s', $this->adminUri(), $entry->id))
            ->getContent();

        // 成否を見ずに json を読む形が残っていないこと
        $this->assertStringContainsString('veinReadJson', $html);
        $this->assertStringNotContainsString('.then(response => response.json())', $html);
    }
}
