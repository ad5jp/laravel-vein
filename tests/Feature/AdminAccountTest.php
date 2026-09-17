<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Auth\AdminGuard;
use AD5jp\Vein\Auth\LockoutGuard;
use AD5jp\Vein\Form\Input\InputPassword;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
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

    /**
     * ログインできて、かつノードでもある行を作ってログインする。
     *
     * 利用側の管理者マスタと同じ形。Fixtures に置くと NodeManager が拾って
     * メニューが増えるため、その場で作る。
     */
    private function signInAsNode(string $title = '本人'): TestEntry
    {
        $user = new class extends TestEntry implements AuthenticatableContract
        {
            use Authenticatable;
        };

        $user->title = $title;
        $user->save();

        config()->set('auth.providers.test_users.model', $user::class);

        $this->actingAs($user);

        return $user;
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

    /** 弾いた理由が画面に出る。出さないと、押しても何も起きないように見える。 */
    public function test_弾いた理由が画面に出る(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)->post($this->url('password'), [
            'current_password' => 'まちがい',
            'password' => 'secret-2',
            'password_confirmation' => 'secret-2',
        ]);

        $this->actingAs($user)
            ->get($this->url('password'))
            ->assertOk()
            ->assertSee('いまのパスワードが違います。');
    }

    /**
     * 自分のパスワードは、この欄では変えない。
     *
     * 専用の画面は「いまのパスワード」を求めるのに、一覧から自分の行を開けば
     * 素通りで変えられた。**迂回路があるほうに合わせる。**
     */
    public function test_自分の行ではパスワード欄を出さない(): void
    {
        $me = $this->signInAsNode();

        $html = (new InputPassword(key: 'password', label: 'パスワード'))
            ->hint('変えないときは空のままにします')
            ->renderColumn($me);

        $this->assertStringNotContainsString('type="password"', $html);
        // 消すだけだと、どこで変えるのか分からなくなる
        $this->assertStringContainsString(route('vein.password'), $html);
        // 当てはまらなくなったヒントは残さない
        $this->assertStringNotContainsString('空のままにします', $html);
    }

    /**
     * 自分の行を描いても、次の行のヒントと必須の印が残る。
     *
     * 欄の実体は行をまたいで使い回される（@see Records::renderFields）。自分の行で
     * 消しっぱなしにすると、後ろの行から黙って落ちる。
     */
    public function test_自分の行を描いても次の行に影響しない(): void
    {
        $me = $this->signInAsNode();
        $other = TestEntry::create(['title' => 'ほかの人']);

        $field = (new InputPassword(key: 'password', label: 'パスワード', required: true))
            ->hint('変えないときは空のままにします');

        $field->renderColumn($me);
        $html = $field->renderColumn($other);

        $this->assertStringContainsString('空のままにします', $html);
        $this->assertStringContainsString('text-danger', $html, '必須の印');
    }

    /** 画面を隠すだけでは足りない。組み立てた POST でも書き換えさせない。 */
    public function test_自分の行は送られてきても書き換えない(): void
    {
        $me = $this->signInAsNode();

        $saved = (new InputPassword(key: 'password'))->beforeSave($me, ['password' => 'yokose-1']);

        $this->assertNull($saved->getAttribute('password'));
    }

    /** ほかの人の行では、今までどおり欄が出て、設定できる。 */
    public function test_ほかの人の行では今までどおり設定できる(): void
    {
        $this->signInAsNode();

        $other = TestEntry::create(['title' => 'ほかの人']);

        $html = (new InputPassword(key: 'password', label: 'パスワード'))->renderColumn($other);
        $this->assertStringContainsString('type="password"', $html);

        $saved = (new InputPassword(key: 'password'))->beforeSave($other, ['password' => 'secret-9']);
        $this->assertSame('secret-9', $saved->getAttribute('password'));
    }

    // ── 自分が誰か分かる ────────────────────────────

    /**
     * ログイン中のアカウントをヘッダーに出す。
     *
     * 管理者が複数居ると、「自分は消せない」も「パスワードの変更」も、どの
     * アカウントの話なのか画面から読み取れなくなる。
     */
    public function test_ヘッダーにログイン中のアカウントが出る(): void
    {
        $this->actingAs($this->makeUser('me@example.com'))
            ->get($this->url(''))
            ->assertOk()
            ->assertSee('me@example.com')
            // 操作（パスワードの変更・ログアウト）はここにぶら下げる
            ->assertSee('パスワードの変更')
            ->assertSee('ログアウト');
    }

    /**
     * 呼び名はノードの一覧の先頭列から取る。
     *
     * どの列が名前かは導入先ごとに違う。取り決めを新しく作らず、一覧でその行を
     * 見分けるために選ばれた列をそのまま使う。
     */
    public function test_呼び名は一覧の先頭列から取る(): void
    {
        $this->signInAsNode('呼ばれたい名前');

        $this->assertSame('呼ばれたい名前', AdminGuard::displayName());
    }

    /** ノードでなければ name 列を見る。それも無ければメールアドレスだけで出す。 */
    public function test_ノードでなければ_name_列を使う(): void
    {
        $this->actingAs($this->makeUser('only-mail@example.com'));

        $this->assertSame('管理者', AdminGuard::displayName());
        $this->assertSame('only-mail@example.com', AdminGuard::label());
    }

    /** ログインしていない画面には出さない。 */
    public function test_ログイン前は出さない(): void
    {
        $this->makeUser('me@example.com');

        $this->get($this->url('signin'))
            ->assertOk()
            ->assertDontSee('me@example.com');
    }

    /**
     * 一覧で自分の行が分かる。
     *
     * ログインできるモデルをノードにした状態を作る（provider をノードへ向ける）。
     * TestEntry と TestUser は別の表で、どちらも id が 1 から振られる。1 件目
     * どうしなら重なるので、それを使って「自分」を作る。
     */
    public function test_一覧で自分の行に印が付く(): void
    {
        $me = TestEntry::create(['title' => 'わたし']);
        TestEntry::create(['title' => 'ほかの人']);

        config()->set('auth.providers.test_users.model', TestEntry::class);

        $user = $this->makeUser();
        $this->assertSame($me->getKey(), $user->getKey(), 'id が重ならないと「自分」にならない');

        $html = $this->actingAs($user)
            ->get($this->url('test_entry'))
            ->assertOk()
            ->getContent();

        // 並び順に頼らず、印がどの行に付いたかで見る
        $this->assertMatchesRegularExpression('/わたし\s*<span class="badge[^"]*">自分<\/span>/u', $html);
        $this->assertSame(1, substr_count($html, '>自分</span>'), '印が付くのは 1 行だけ');
    }

    /**
     * 消せない行では、削除ボタンを出さずに理由を書く。
     *
     * 押させて「元に戻せません」を通らせたうえで断ると、確認の重みが薄れる。
     */
    public function test_消せない行では削除ボタンを出さない(): void
    {
        $entry = TestEntry::create(['title' => '最後の 1 人']);

        config()->set('auth.providers.test_users.model', TestEntry::class);

        // id を重ねない。ここで見たいのは「自分」ではなく「最後の 1 人」の側
        $this->makeUser('first@example.com');

        $this->actingAs($this->makeUser('second@example.com'))
            ->get($this->url("test_entry/{$entry->getKey()}"))
            ->assertOk()
            ->assertSee('ログインできる人が居なくなるため削除できません。')
            // 文言ではなくボタンそのもので見る。削除フォームへ送る唯一の要素
            ->assertDontSee('form="delete"', false);
    }

    /** ふつうの行では、今までどおり削除ボタンが出る。 */
    public function test_消せる行では削除ボタンが出る(): void
    {
        $entry = TestEntry::create(['title' => '消せる']);

        $this->actingAs($this->makeUser())
            ->get($this->url("test_entry/{$entry->getKey()}"))
            ->assertOk()
            ->assertSee('form="delete"', false);
    }

    // ── パスワードが変わったら席を空ける ──────────────

    /**
     * ほかの管理者にパスワードを変えられたら、その人のセッションは切れる。
     *
     * `logoutOtherDevices()` は自分にしか使えないため、他人のパスワードを変えても
     * その人のセッションは残っていた。**乗っ取られた人のパスワードを変える**という、
     * いちばん効いてほしい場面で効かないことになる。
     */
    public function test_パスワードを変えられた人のセッションが切れる(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)->get($this->url(''))->assertOk();

        // 別の管理者が、この人のパスワードを変えた
        $user->forceFill(['password' => 'secret-2'])->save();

        $this->get($this->url(''))->assertRedirect(route('vein.signin'));
    }

    /**
     * 自分で変えたときは、その画面の操作を続けられる。
     *
     * 画面で「変えたあともこの画面の操作は続けられます」と約束している。
     * ここで切れると、変えた直後に締め出されたように見える。
     */
    public function test_自分で変えたときは締め出されない(): void
    {
        $user = $this->makeUser(password: 'secret-1');

        $this->actingAs($user)->get($this->url(''))->assertOk();

        $this->post($this->url('password'), [
            'current_password' => 'secret-1',
            'password' => 'secret-2',
            'password_confirmation' => 'secret-2',
        ])->assertSessionHasNoErrors();

        $this->get($this->url(''))->assertOk();
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
