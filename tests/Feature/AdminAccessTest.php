<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_ログイン画面が描画できる(): void
    {
        $this->get('/'.config('vein.admin_uri').'/signin')->assertOk();
    }

    public function test_未認証なら管理画面からログインへ飛ぶ(): void
    {
        $this->get('/'.config('vein.admin_uri'))
            ->assertRedirect(route('vein.signin'));
    }

    public function test_認証済みなら管理画面が開ける(): void
    {
        $admin = TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->actingAs($admin)
            ->get('/'.config('vein.admin_uri'))
            ->assertOk();
    }
}
