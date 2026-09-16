<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\ScopesErrorKeys;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class FormControl implements ScopesErrorKeys
{
    /**
     * エラーを引くときのキーの接頭辞。
     *
     * 子レコードの中では、検証のキーが `images.0.caption` のように親と添字を含む。
     * 描画の時点では自分が何行目かを知らないため、Records が行ごとに教える。
     */
    protected ?string $error_key_prefix = null;

    /** ラベルの近くに置く補足。ラベルに詰め込むと長くなる説明はここへ。 */
    protected ?string $hint = null;

    /**
     * ラベルを入力に結びつけるか。
     *
     * 入力を 1 つだけ持つ欄は true にする。ラベルを押して欄に入れるようになり、
     * 読み上げにも欄の名前が伝わる。束ねた欄や、ラベルが入力を包む作り
     * （チェックボックス・ラジオ）は結びつける先が定まらないので false のまま。
     */
    protected bool $labelled_input = false;

    public function __construct(
        public string $key,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 4,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
        public ?string $placeholder = null,
    ) {}

    /**
     * 補足を出す。欄なら入力の下、子レコードなら見出しの下。
     *
     * 「どこに出るか」「何字が目安か」のような説明をラベルに入れると、ラベルが
     * 読みづらくなる。子レコードではラベルが追加ボタンの名前にもなるため、
     * 説明を混ぜるとボタンまで長くなる。名前はラベル、説明はこちらへ分ける。
     */
    public function hint(string $text): static
    {
        $this->hint = $text;

        return $this;
    }

    /**
     * placeholder 属性。指定が無ければ空文字。
     *
     * 入力例はラベルに書かず、こちらへ入れる。ラベルは子レコードの追加ボタンの
     * 名前にもなるため、例を混ぜると長くなる。
     */
    protected function placeholderAttribute(): string
    {
        return $this->placeholder === null
            ? ''
            : sprintf(' placeholder="%s"', e($this->placeholder));
    }

    /**
     * ラベルと結びつけるための id。結びつけない欄では null。
     *
     * 子レコードの中では名前が officers[0][name] の形に書き換わる。id も同じ
     * 規則で書き換えられるよう、決まった接頭辞を付けておく。
     *
     * @see Records::wrapKeys()
     */
    protected function inputId(): ?string
    {
        return $this->labelled_input ? '__f_'.$this->key : null;
    }

    /**
     * 入力に付ける属性。ラベルと結ぶ id と、必須であること。
     *
     * HTML の required は付けない。一覧で畳んだ欄が必須だと、ブラウザが
     * 「見えない欄が空だ」と言って送信を止め、しかも何も示せなくなる。
     */
    protected function inputAttributes(): string
    {
        $id = $this->inputId();

        return ($id === null ? '' : sprintf(' id="%s"', e($id)))
            .($this->required ? ' aria-required="true"' : '');
    }

    /**
     * 保存の前処理。
     *
     * ここは上書きしない。要素ごとの処理は applyBeforeSave() に書く。
     * beforeSaving の呼び出しをここに集めておかないと、要素を足すたびに
     * 配線を書き忘れる。
     */
    public function beforeSave(Model $model, Arrayable|array $request): Model
    {
        if ($request instanceof Arrayable) {
            $request = $request->toArray();
        }

        $model = $this->applyBeforeSave($model, $request);

        if ($this->beforeSaving) {
            $model = ($this->beforeSaving)($model, $request) ?? $model;
        }

        return $model;
    }

    /**
     * 保存の後処理。上書きしない（applyAfterSave() に書く）。
     */
    public function afterSave(Model $model, Arrayable|array $request): Model
    {
        if ($request instanceof Arrayable) {
            $request = $request->toArray();
        }

        $model = $this->applyAfterSave($model, $request);

        if ($this->afterSaving) {
            $model = ($this->afterSaving)($model, $request) ?? $model;
        }

        return $model;
    }

    /**
     * 要素ごとの保存前処理。上書きするならこちら。
     *
     * @param  array<string, mixed>  $request
     */
    protected function applyBeforeSave(Model $model, array $request): Model
    {
        $model->{$this->key} = $request[$this->key] ?? null;

        return $model;
    }

    /**
     * 要素ごとの保存後処理。上書きするならこちら。
     *
     * @param  array<string, mixed>  $request
     */
    protected function applyAfterSave(Model $model, array $request): Model
    {
        return $model;
    }

    /**
     * この要素が要求するバリデーション規則。
     *
     * required を指定した要素は、ここで規則を出す。
     * 子を持つ要素（Group / Row / Records）は子の分をまとめて返す。
     *
     * @return array<string, mixed>
     */
    public function validationRules(Model $model): array
    {
        if (! $this->required) {
            return [];
        }

        return [$this->key => ['required']];
    }

    public function searchQuery(Builder $builder, Arrayable|array $request): Builder
    {
        if ($request instanceof Arrayable) {
            $request = $request->toArray();
        }

        $value = $request[$this->key] ?? null;

        if ($value === null) {
            return $builder;
        }

        if ($this->searching) {
            return ($this->searching)($builder, $value);
        }

        return $builder->where($this->key, $value);
    }

    public function afterSearch(Model $model, Arrayable|array $request): Model
    {
        if ($request instanceof Arrayable) {
            $request = $request->toArray();
        }

        $model->{$this->key} = $request[$this->key] ?? null;

        return $model;
    }

    public function render(Model $values): string
    {
        return sprintf(
            '<div class="row mb-3">%s</div>',
            $this->renderColumn($values),
        );
    }

    public function renderColumn(Model $values): string
    {
        $html = $this->renderInline($values);

        if ($this->hint !== null) {
            $html .= sprintf('<p class="__field_hint">%s</p>', e($this->hint));
        }

        if ($this->label) {
            $id = $this->inputId();

            $html = sprintf(
                '<label class="form-label"%s>%s%s</label>%s',
                $id === null ? '' : sprintf(' for="%s"', e($id)),
                e($this->label),
                // 必須は目で分かるようにする。読み上げには入力側の aria-required が伝える
                $this->required ? '<span class="text-danger ms-1" aria-hidden="true">*</span>' : '',
                $html,
            );
        }

        // 弾かれた理由は、画面の上にまとめるだけでなく、その欄のそばにも出す。
        // 入力欄が多いと、上の一覧だけではどこを直せばよいか分からない
        $error = $this->errorMessage();

        if ($error !== null) {
            $html .= sprintf('<p class="__field_error">%s</p>', e($error));
        }

        $medium = $this->colSize;
        $medium = $medium > 12 ? 12 : $medium;

        $small = $this->colSize * 2;
        $small = $small > 12 ? 12 : $small;

        $html = sprintf(
            '<div class="col-md-%s col-sm-%s col-12%s">%s</div>',
            $medium,
            $small,
            $error !== null ? ' __has_error' : '',
            $html,
        );

        return $html;
    }

    /**
     * この欄のエラー文言。無ければ null。
     */
    protected function errorMessage(): ?string
    {
        $errors = session()->get('errors');

        if ($errors === null) {
            return null;
        }

        $key = $this->error_key_prefix === null
            ? $this->key
            : $this->error_key_prefix.'.'.$this->key;

        $bag = $errors->getBag('default');

        return $bag->has($key) ? $bag->first($key) : null;
    }

    /**
     * 子レコードの中で描かれるとき、親のキーと添字を受け取る。
     *
     * @see Records::renderRow()
     */
    public function withErrorKeyPrefix(?string $prefix): static
    {
        $this->error_key_prefix = $prefix;

        return $this;
    }

    abstract public function renderInline(Model $values): string;

    protected function getValue(Model $values): mixed
    {
        return old($this->key, $values->{$this->key}) ?? $this->default;
    }
}
