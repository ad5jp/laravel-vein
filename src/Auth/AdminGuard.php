<?php

declare(strict_types=1);

namespace AD5jp\Vein\Auth;

use AD5jp\Vein\Node\Attributes\ListField;
use AD5jp\Vein\Node\Contracts\Entry;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;
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
     * いまログインしている人を画面に出すときの呼び名。
     *
     * ログインはメールアドレスで受けているので（@see SigninController）、
     * そのまま出せば「どのアカウントか」が過不足なく分かる。名前を出す取り決めを
     * 別に設けると、導入先ごとに列名が違って決まらない。
     */
    public static function label(): ?string
    {
        $user = self::user();

        if ($user === null) {
            return null;
        }

        $email = $user instanceof Model ? $user->getAttribute('email') : null;

        if (is_string($email) && $email !== '') {
            return $email;
        }

        // メールアドレスを持たない作りでも、無言になるよりは何か出す
        return (string) $user->getAuthIdentifier();
    }

    /**
     * いまログインしている人の呼び名。分からなければ null。
     *
     * どの列が名前かは導入先ごとに違う。取り決めを新しく作らず、ノードにしている
     * なら**一覧の先頭列**を使う。一覧でその行を見分けるために選んだ列なので、
     * 人を見分ける呼び名としてもそのまま通る。
     */
    public static function displayName(): ?string
    {
        $user = self::user();

        if (! $user instanceof Model) {
            return null;
        }

        if ($user instanceof Entry) {
            $fields = ListField::parse($user->listFields());
            $name = isset($fields[0]) ? trim((string) $fields[0]->getValue($user)) : '';

            if ($name !== '') {
                return $name;
            }
        }

        $name = $user->getAttribute('name');

        return is_string($name) && $name !== '' ? $name : null;
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
