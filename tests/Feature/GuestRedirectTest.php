<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\TestCase;

/**
 * 未認証時の飛び先を、アプリ全体ではなく vein のルートだけで決めていること。
 *
 * Authenticate::redirectUsing() は静的なグローバル上書きなので、vein が呼ぶと
 * ホストアプリのルートまで巻き込む。呼んでいないことは、ホスト側のルートが
 * フレームワーク既定のまま振る舞うかどうかで確かめる。
 */
class GuestRedirectTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => 'login')->name('login');
        $router->middleware(['web', 'auth'])->get('/host-only', fn () => 'ok');
    }

    public function test_ホスト側のルートはフレームワーク既定の飛び先を使う(): void
    {
        $this->get('/host-only')->assertRedirect(route('login'));
    }

    public function test_管理画面はログイン画面へ飛ぶ(): void
    {
        $this->get('/'.config('vein.admin_uri'))->assertRedirect(route('vein.signin'));
    }

    public function test_管理画面の飛び先はホスト側に漏れない(): void
    {
        // vein のルートを踏んだ後でも、ホスト側の飛び先は変わらない
        $this->get('/'.config('vein.admin_uri'))->assertRedirect(route('vein.signin'));

        $this->get('/host-only')->assertRedirect(route('login'));
    }
}
