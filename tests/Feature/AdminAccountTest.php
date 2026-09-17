<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Auth\LockoutGuard;
use AD5jp\Vein\Form\Input\InputPassword;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

/**
 * 管理者アカウントの操作。
 *
 * 失敗の仕方が「誰も管理画面に入れない」になるので、戻すには seeder か tinker が要る。
 * パスワードを空で潰す・自分を消す・最後の 1 人を消す、の 3 つを止める。
 */
class AdminAccountTest extends TestCase
{
    private function makeUser(string $email = 'admin@example.com', string $password = 'secret-1'): TestUser
    {
        return TestUser::create([
            'name' => '管理者',
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function url(string $path): string
    {
        return sprintf('/%s/%s', config('vein.admin_uri'), $path);
    }

    // ── パスワードの欄 ──────────────────────────────

    /** 素の InputText で出すと value にハッシュが載る。それをしない。 */
    public function test_保存済みのパスワードを画面に出さない(): void
    {
        $user = $this->makeUser();
        $stored = $user->getAuthPassword();

        $html = (new InputPassword(key: 'password', label: 'パスワード'))->renderInline($user);

        $this->assertStringNotContainsString($stored, $html);
        $this->assertStringNotContainsString('value=', $html);
        $this->assertStringContainsString('type="password"', $html);
    }

    /** 確認用の欄も出す。Laravel の confirmed 規則がこの名前を探す。 */
    public function test_確認用の欄も出す(): void
    {
        $user = $this->makeUser();

        $html = (new InputPassword(key: 'password'))->renderInline($user);

        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
    }

    /**
     * 空で送られたら触らない。
     *
     * 既定の実装は「送られてこなければ null を入れる」。名前だけ直したつもりの
     * 保存でハッシュが消え、その人が入れなくなる。
     */
    public function test_空で保存してもパスワードが変わらない(): void
    {
        $user = $this->makeUser(password: 'secret-1');
        $before = $user->getAuthPassword();

        $field = new InputPassword(key: 'password');

        foreach ([[], ['password' => ''], ['password' => null]] as $request) {
            $field->beforeSave($user, $request);
            $user->save();

            $this->assertSame($before, $user->fresh()->getAuthPassword());
        }

        $this->assertTrue(Hash::check('secret-1', $user->fresh()->getAuthPassword()));
    }

    /** 値があれば変わる。ハッシュ化はモデルのキャストが受け持つ。 */
    public function test_値があれば変わり_ハッシュ化される(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        (new InputPassword(key: 'password'))->beforeSave($user, ['password' => 'secret-2']);
        $user->save();

        $stored = $user->fresh()->getAuthPassword();

        $this->assertNotSame('secret-2', $stored, '平文のまま入っている');
        $this->assertTrue(Hash::check('secret-2', $stored));
    }

    /**
     * 二重にハッシュ化しない。
     *
     * 欄がハッシュ化すると、hashed キャストを持つモデルで 2 回かかり、
     * 元のパスワードでは入れなくなる。
     */
    public function test_二重にハッシュ化されない(): void
    {
        $user = $this->makeUser(password: 'secret-1');
        $hashed = $user->getAuthPassword();

        // すでにハッシュ化された値を入れ直しても、そのまま保たれる
        (new InputPassword(key: 'password'))->beforeSave($user, ['password' => $hashed]);
        $user->save();

        $this->assertSame($hashed, $user->fresh()->getAuthPassword());
        $this->assertTrue(Hash::check('secret-1', $user->fresh()->getAuthPassword()));
    }

    /** 編集では空を許す。新規では必須にできる。 */
    public function test_編集では空を許し_新規では必須にできる(): void
    {
        $field = new InputPassword(key: 'password', required: true);

        $this->assertSame(['password' => ['required', 'confirmed']], $field->validationRules(new TestUser));
        $this->assertSame(['password' => ['nullable', 'confirmed']], $field->validationRules($this->makeUser()));
    }

    // ── パスワードを変える画面 ──────────────────────

    public function test_自分のパスワードを変えられる(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)
            ->post($this->url('password'), [
                'current_password' => 'secret-1',
                'password' => 'secret-2',
                'password_confirmation' => 'secret-2',
            ])
            ->assertRedirect()
            ->assertSessionHas('message.success');

        $this->assertTrue(Hash::check('secret-2', $user->fresh()->getAuthPassword()));
    }

    /** 変えたあともログインは保つ（他の端末の分だけ切る）。 */
    public function test_変えたあともログインが保たれる(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)->post($this->url('password'), [
            'current_password' => 'secret-1',
            'password' => 'secret-2',
            'password_confirmation' => 'secret-2',
        ]);

        $this->actingAs($user->fresh())->get($this->url(''))->assertOk();
    }

    /** いまのパスワードを求めるのは、席を離れた隙に変えられるのを防ぐため。 */
    public function test_いまのパスワードが違うと弾かれる(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)
            ->post($this->url('password'), [
                'current_password' => 'まちがい',
                'password' => 'secret-2',
                'password_confirmation' => 'secret-2',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret-1', $user->fresh()->getAuthPassword()));
    }

    public function test_確認用と食い違うと弾かれる(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)
            ->post($this->url('password'), [
                'current_password' => 'secret-1',
                'password' => 'secret-2',
                'password_confirmation' => 'secret-3',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('secret-1', $user->fresh()->getAuthPassword()));
    }

    // ── 締め出し防止 ────────────────────────────────

    public function test_自分自身は消せない(): void
    {
        $me = $this->makeUser('me@example.com');
        $this->makeUser('other@example.com');

        $this->actingAs($me);

        $this->assertSame('自分のアカウントは削除できません。', LockoutGuard::reasonToKeep($me));
    }

    public function test_最後の1人は消せない(): void
    {
        $last = $this->makeUser('last@example.com');
        $other = $this->makeUser('other@example.com');

        $this->actingAs($other);

        // 2 人居るうちは消せる
        $this->assertNull(LockoutGuard::reasonToKeep($last));

        $other->delete();

        $this->assertSame('ログインできる人が居なくなるため削除できません。', LockoutGuard::reasonToKeep($last->fresh()));
    }

    /** ログインできるモデルでなければ、何も止めない。 */
    public function test_ログインできるモデル以外は素通りする(): void
    {
        $this->actingAs($this->makeUser());

        $this->assertNull(LockoutGuard::reasonToKeep(TestEntry::create(['title' => '親'])));
    }

    /**
     * 削除の入口で止まる。
     *
     * ログインできるモデルをノードにした状態を作るため、ガードの provider を
     * ノードのモデルへ向ける。ここで見たいのは「削除の経路に関門があるか」だけ。
     */
    public function test_削除の入口で止まる(): void
    {
        $entry = TestEntry::create(['title' => 'ログインできるノード']);

        config()->set('auth.providers.test_users.model', TestEntry::class);

        // ログイン中の id と対象の id が重なると「自分自身」で止まり、
        // 見たい経路（最後の 1 人）を通らない。2 人目でログインして重なりを外す
        $this->makeUser('first@example.com');

        $this->actingAs($this->makeUser('second@example.com'))
            ->post($this->url("test_entry/{$entry->id}/delete"))
            ->assertRedirect()
            ->assertSessionHas('message.error', 'ログインできる人が居なくなるため削除できません。');

        $this->assertNotNull(TestEntry::find($entry->id), '消えてしまっている');
    }
}
