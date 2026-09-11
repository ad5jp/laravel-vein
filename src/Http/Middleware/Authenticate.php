<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * 管理画面用の認証ミドルウェア。
 *
 * 未認証時の飛び先をここで決める。Authenticate::redirectUsing() は静的な
 * グローバル上書きで、vein 以外のルートにも効いてしまうため使わない。
 */
class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): string
    {
        return route('vein.signin');
    }
}
