<?php

declare(strict_types=1);

namespace AD5jp\Vein\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Auth;

/**
 * 管理画面にログインするためのガード。
 *
 * どのガードを使うかは利用側が config で決める。解決の式が散ると、
 * 片方だけ直したときに「ログインは通るのに判定は別のガードを見ている」という
 * 食い違いが起きるので、ここに集める。
 */
class AdminGuard
{
    public static function name(): string
    {
        return config('vein.admin_guard') ?? config('auth.defaults.guard');
    }

    public static function guard(): StatefulGuard
    {
        /** @var StatefulGuard */
        return Auth::guard(self::name());
    }

    public static function user(): ?Authenticatable
    {
        return self::guard()->user();
    }

    /**
     * ログインできるモデルのクラス名。
     *
     * 「いま消そうとしている行が、ログインできるモデルか」の判定に使う。
     * Eloquent 以外の provider（database ドライバ等）では分からないので null。
     */
    public static function model(): ?string
    {
        $provider = self::provider();

        return $provider instanceof EloquentUserProvider ? $provider->getModel() : null;
    }

    private static function provider(): ?UserProvider
    {
        $name = config('auth.guards.'.self::name().'.provider');

        return is_string($name) ? Auth::createUserProvider($name) : null;
    }
}
