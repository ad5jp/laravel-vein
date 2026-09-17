<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Auth\AdminGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 自分のパスワードを変える。
 *
 * ノードの編集画面でも変えられるが、そちらは利用側がログインできるモデルを
 * ノードにしているときだけ使える。ここは vein 単体で成立させる。
 */
class PasswordController extends Controller
{
    public function init(): View
    {
        return view('vein::password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = AdminGuard::user();

        if ($user === null) {
            return redirect()->to(route('vein.signin'));
        }

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed'],
        ], [], [
            'current_password' => 'いまのパスワード',
            'password' => '新しいパスワード',
        ]);

        // いまのパスワードを求めるのは、席を離れた隙に変えられるのを防ぐため
        if (! Hash::check($request->string('current_password')->toString(), $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => 'いまのパスワードが違います。',
            ]);
        }

        $password = $request->string('password')->toString();

        // ハッシュ化はモデルの hashed キャストに任せる（InputPassword と同じ約束）
        $user->forceFill(['password' => $password])->save();

        // 自分のログインは保ち、ほかの端末で開いている分だけ切る
        AdminGuard::guard()->logoutOtherDevices($password);

        return back()->with('message.success', 'パスワードを変えました');
    }
}
