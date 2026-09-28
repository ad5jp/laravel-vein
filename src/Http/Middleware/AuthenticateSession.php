<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession as Middleware;

/**
 * パスワードが変わった人のセッションを、次のリクエストで切る。
 *
 * `logoutOtherDevices()` は**自分にしか使えない**。管理者が他人のパスワードを
 * 変えても、その人のセッションは生きたまま残っていた。乗っ取られた人の
 * パスワードを変える、といういちばん効いてほしい場面で効かない。
 *
 * このミドルウェアは、ログイン時のパスワードのハッシュをセッションに控え、毎回
 * 現在の値と突き合わせる。誰が変えたかに関係なく、変わった時点でそのセッションは
 * 通らなくなる。
 *
 * **Authenticate より後ろに置くこと。** 本体は既定のガードを見る作りだが、
 * `Authenticate` が `shouldUse()` で既定を認証に使ったガードへ差し替えるため、
 * 後ろに置けば vein の admin_guard 設定と食い違わない。
 *
 * @see Authenticate
 * @see routes/vein.php
 */
class AuthenticateSession extends Middleware
{
    /**
     * 切られたあとの飛び先。
     *
     * 本体の redirectUsing() は静的なグローバル上書きで、vein 以外のルートにも
     * 効いてしまうため使わない（Authenticate と同じ理由）。
     */
    protected function redirectTo(Request $request): string
    {
        return route('vein.signin');
    }
}
