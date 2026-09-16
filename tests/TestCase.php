<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests;

use AD5jp\Vein\ServiceProvider;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * テストの土台。
 *
 * インメモリの SQLite に Fixtures のテーブルを作り、vein の ServiceProvider を読み込む。
 * 管理画面は config/auth.php のデフォルトガードを使うため、ここで TestUser を
 * プロバイダに指定している。
 *
 * いまのところ、管理画面に HTTP で入るテストは書けない。パッケージ側に 2 つ理由がある。
 *
 * 1. NavigationManager がメニューを組むときに base_path('vendor/composer/autoload_psr4.php')
 *    を読む。テスト環境では base_path() がパッケージのルートを指さないため見つからない
 * 2. ServiceProvider が Authenticate::redirectUsing() でアプリ全体の未認証リダイレクトを
 *    差し替えるが、テスト環境ではフレームワーク既定に上書きし返されて route('login') に飛ぶ
 *
 * どちらもパッケージ本体の課題で、この土台を入れる変更では直さない。
 * それまでは、フォームコントロールやモデルの単体テストとして書く。
 */
abstract class TestCase extends Orchestra
{
    /**
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        tap($app->make(Repository::class), function (Repository $config): void {
            // HTTP を叩くテストで暗号化キーが要る。値は毎回作り捨て。
            $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

            $config->set('database.default', 'testing');

            $config->set('auth.defaults.guard', 'web');
            $config->set('auth.guards.web', [
                'driver' => 'session',
                'provider' => 'test_users',
            ]);
            $config->set('auth.providers.test_users', [
                'driver' => 'eloquent',
                'model' => TestUser::class,
            ]);

            // Node を探す名前空間。先頭のバックスラッシュは必須。
            $config->set('vein.model_namespaces', ['\\'.__NAMESPACE__.'\\Fixtures']);
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createFixtureSchema();
    }

    /**
     * Fixtures のテーブルを作る。
     *
     * 親 1 件に子 N 件がぶら下がる最小構成。Records フォームコントロールの
     * 挙動（AD5-58 以降）を確かめられる形にしてある。
     */
    protected function createFixtureSchema(): void
    {
        Schema::create((new TestUser)->getTable(), function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create((new TestEntry)->getTable(), function ($table): void {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create((new TestRecord)->getTable(), function ($table): void {
            $table->id();
            $table->foreignId('test_entry_id');
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    protected function getApplicationTimezone($app): string
    {
        return 'Asia/Tokyo';
    }
}
