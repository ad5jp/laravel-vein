<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Auth\AdminGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SigninController extends Controller
{
    public function init(): RedirectResponse|View
    {
        if (AdminGuard::guard()->check()) {
            return redirect()->to(route('vein.home'));
        }

        return view('vein::signin');
    }

    public function signin(Request $request): RedirectResponse
    {
        // TODO ログイン用のフィールド違いに対応
        if (AdminGuard::guard()->attempt($request->only(['email', 'password']))) {
            $request->session()->regenerate();

            return redirect()->intended(route('vein.home'));
        }

        // withInput() は ValidationException 経由でないと password をフィルタしないため、
        // 戻す項目を明示する
        return back()
            ->withInput($request->only('email'))
            ->with('message.error', 'メールアドレスかパスワードが間違っています。');
    }

    public function signout(Request $request): RedirectResponse
    {
        AdminGuard::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(route('vein.signin'))->with('message.success', 'ログアウトしました');
    }
}
