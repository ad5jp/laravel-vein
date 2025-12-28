<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SigninController extends Controller
{
    public function init(): RedirectResponse|View
    {
        $guard = config('vein.admin_guard') ?? config('auth.defaults.guard');

        if (Auth::guard($guard)->check()) {
            return redirect()->to(route('vein.home'));
        }

        return view('vein::signin');
    }

    public function signin(Request $request): RedirectResponse
    {
        $guard = config('vein.admin_guard') ?? config('auth.defaults.guard');

        // TODO ログイン用のフィールド違いに対応
        if (Auth::guard($guard)->attempt($request->only(['email', 'password']))) {
            $request->session()->regenerate();

            return redirect()->intended(route('vein.home'));
        }

        return back()->withInput()->with('message.error', 'メールアドレスかパスワードが間違っています。');
    }

    public function signout(Request $request): RedirectResponse
    {
        $guard = config('vein.admin_guard') ?? config('auth.defaults.guard');

        Auth::guard($guard)->logout();

        return redirect()->to(route('vein.signin'))->with('message.success', 'ログアウトしました');
    }
}
