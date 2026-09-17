<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Auth\LockoutGuard;
use AD5jp\Vein\Form\Contracts\Form;
use Illuminate\Database\Eloquent\Model;

/**
 * パスワードの欄。
 *
 * **ハッシュ化はしない。モデルの責任。** Laravel の `hashed` キャストを付けておけば、
 * 平文だけがハッシュ化され、二重にはならない。ここで握ると、キャストを持つモデルで
 * 二重にハッシュ化され、誰も入れなくなる。
 *
 *     protected function casts(): array
 *     {
 *         return ['password' => 'hashed'];
 *     }
 *
 * この欄がやるのは 3 つ。
 *
 *   1. 保存済みの値を画面に出さない（素の InputText だと value にハッシュが載る）
 *   2. 空で送られたら、その欄を保存の対象から外す（変えないつもりの保存で消さない）
 *   3. 確認用の再入力と突き合わせる（打ち間違いで入れなくなるのを防ぐ）
 *   4. **自分自身の行では欄を出さない**（下記）
 *
 * ## 自分のパスワードはここでは変えない
 *
 * 専用の画面（@see PasswordController）は「いまのパスワード」を求める。席を離れた
 * 隙に変えられるのを防ぐためだが、**一覧から自分の行を開いて変えられるなら、その
 * 関門は意味を持たない。** 迂回路があるほうに合わせて、ここでは変えさせない。
 *
 * 欄を消すだけだと、どこで変えるのか分からなくなるので行き先を置く。
 */
class InputPassword extends FormControl implements Form
{
    protected bool $labelled_input = true;

    /** その行が、いまログインしている本人か。 */
    private function isSelf(Model $model): bool
    {
        return LockoutGuard::isCurrentUser($model);
    }

    public function renderColumn(Model $values): string
    {
        if (! $this->isSelf($values)) {
            return parent::renderColumn($values);
        }

        // 「変えないときは空のままにします」は当てはまらなくなる。必須の印も外す。
        //
        // **元に戻す。** 欄の実体は行をまたいで使い回される（@see Records::renderFields）
        // ので、消したままにすると次の行からヒントと必須の印が落ちる
        $hint = $this->hint;
        $required = $this->required;

        $this->hint = null;
        $this->required = false;

        try {
            return parent::renderColumn($values);
        } finally {
            $this->hint = $hint;
            $this->required = $required;
        }
    }

    /** 確認用の欄の名前。Laravel の confirmed 規則がこの形を探す。 */
    private function confirmationKey(): string
    {
        return $this->key.'_confirmation';
    }

    public function renderInline(Model $values): string
    {
        if ($this->isSelf($values)) {
            return sprintf(
                '<p class="__field_hint mb-0">%s<a href="%s">%s</a>%s</p>',
                e('自分のパスワードは'),
                e(route('vein.password')),
                e('パスワードの変更'),
                e('から変えます。'),
            );
        }

        // value 属性そのものを付けない。空文字を入れるのではなく、出さない
        return sprintf(
            '<input type="password" name="%s" class="form-control" autocomplete="new-password"%s>'
            .'<input type="password" name="%s" class="form-control mt-2" autocomplete="new-password" placeholder="%s" aria-label="%s">',
            e($this->key),
            $this->placeholderAttribute().$this->inputAttributes($values),
            e($this->confirmationKey()),
            e('確認のためもう一度'),
            e($this->label === null ? '確認のためもう一度' : $this->label.'（確認のためもう一度）'),
        );
    }

    /**
     * 空なら触らない。
     *
     * 既定の実装は「送られてこなければ null を入れる」。パスワードでそれをやると、
     * 名前だけ直したつもりの保存でハッシュが消え、その人が入れなくなる。
     */
    protected function applyBeforeSave(Model $model, array $request): Model
    {
        // 欄を出していないので、送られてきても受け取らない。
        // 画面を隠すだけでは、組み立てた POST で書き換えられる
        if ($this->isSelf($model)) {
            return $model;
        }

        $value = $request[$this->key] ?? null;

        if ($value === null || $value === '') {
            return $model;
        }

        $model->{$this->key} = $value;

        return $model;
    }

    /**
     * 確認用と一致すること。
     *
     * 新規では必須にできるが、**編集では空を許す**。空は「変えない」の意味で、
     * 必須にすると開くたびに打ち直させることになる。
     */
    public function validationRules(Model $model): array
    {
        if ($this->isSelf($model)) {
            return [];
        }

        $rules = ['confirmed'];

        if ($this->required && ! $model->exists) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return [$this->key => $rules];
    }
}
