<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class FormControl
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 4,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
    ) {}

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

        if ($this->label) {
            $html = sprintf('<label class="form-label">%s</label>%s', e($this->label), $html);
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

        $bag = $errors->getBag('default');

        return $bag->has($this->key) ? $bag->first($this->key) : null;
    }

    abstract public function renderInline(Model $values): string;

    protected function getValue(Model $values): mixed
    {
        return old($this->key, $values->{$this->key}) ?? $this->default;
    }
}
