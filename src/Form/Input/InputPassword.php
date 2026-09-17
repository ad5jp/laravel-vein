<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

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
 */
class InputPassword extends FormControl implements Form
{
    protected bool $labelled_input = true;

    /** 確認用の欄の名前。Laravel の confirmed 規則がこの形を探す。 */
    private function confirmationKey(): string
    {
        return $this->key.'_confirmation';
    }

    public function renderInline(Model $values): string
    {
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
        $rules = ['confirmed'];

        if ($this->required && ! $model->exists) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return [$this->key => $rules];
    }
}
