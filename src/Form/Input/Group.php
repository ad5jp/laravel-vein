<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\ScopesErrorKeys;
use AD5jp\Vein\Form\Contracts\SearchForm;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Group extends FormControl implements Form, SearchForm
{
    public function __construct(
        public ?string $label = null,
        public int $colSize = 12,
        /** @var array<int, Form|string> */
        public array $children = [],
    ) {
        parent::__construct('__', $label, null, $colSize);
    }

    public function renderInline(Model $model): string
    {
        $html = '';

        $html .= '<div class="input-group">';

        $children = array_values($this->children);

        foreach ($children as $i => $child) {
            if ($child instanceof Form) {
                $html .= $child->renderInline($model);

                continue;
            }

            if (! is_string($child)) {
                throw new Exception('Invalid children of Group');
            }

            // 帯の中の文字は、すぐ後ろの欄の名前として置くことが多い。
            // 結びつけておくと、押してその欄に入れる
            $next = $children[$i + 1] ?? null;
            $for = $next instanceof FormControl ? $next->inputId() : null;
            // 帯の中の文字に印を出すのは、束ねた欄そのものに名前が無いときだけ。
            // 名前があるときはそちらに出るので、接頭辞（/works/ など）には要らない
            $next_required = $this->label === null
                && $next instanceof FormControl
                && $next->isRequired($model);

            $html .= $for === null
                ? sprintf('<span class="input-group-text">%s</span>', e($child))
                : sprintf(
                    '<label class="input-group-text" for="%s">%s%s</label>',
                    e($for),
                    e($child),
                    // 必須の印は、欄の上に出すラベルと揃える
                    $next_required ? '<span class="text-danger ms-1" aria-hidden="true">*</span>' : '',
                );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 束ねた欄そのものは値を持たない。中の欄が必須なら、こちらにも印を出す。
     *
     * 接頭辞だけを置く形（/works/ + 入力）では入力側にラベルが無いため、
     * ここが唯一の出し先になる。
     */
    public function isRequired(Model $values): bool
    {
        if (parent::isRequired($values)) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child instanceof FormControl && $child->isRequired($values)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ラベルは中の最初の欄に結ぶ。
     *
     * 束ねた欄そのものは値を持たないが、接頭辞だけを置く形（/works/ + 入力）では
     * 入力側にラベルが無い。結ばないと、読み上げでは欄の名前が「/works/」になる。
     */
    protected function inputId(): ?string
    {
        foreach ($this->children as $child) {
            if ($child instanceof FormControl && ($id = $child->inputId()) !== null) {
                return $id;
            }
        }

        return null;
    }

    /** 子レコードの中では、配られた規則をそのまま子へ渡す。 */
    public function withScopedRules(?array $rules): static
    {
        parent::withScopedRules($rules);

        foreach ($this->children as $child) {
            if ($child instanceof FormControl) {
                $child->withScopedRules($rules);
            }
        }

        return $this;
    }

    /**
     * 束ねた欄も、子レコードの中では行ごとの接頭辞が要る。
     *
     * Group そのものは値を持たない（key は '__'）ため、受け取った接頭辞は子へ渡す。
     */
    public function withErrorKeyPrefix(?string $prefix): static
    {
        parent::withErrorKeyPrefix($prefix);

        foreach ($this->children as $child) {
            if ($child instanceof ScopesErrorKeys) {
                $child->withErrorKeyPrefix($prefix);
            }
        }

        return $this;
    }

    /**
     * 束ねた欄のエラー。
     *
     * Group そのものは値を持たない（key は '__'）ため、そのままだと理由が
     * 画面の上のまとめにしか出ない。子のうち最初に弾かれたものを出す。
     */
    protected function errorMessage(): ?string
    {
        foreach ($this->children as $child) {
            if (! $child instanceof FormControl) {
                continue;
            }

            $message = $child->errorMessage();

            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    public function validationRules(Model $model): array
    {
        $rules = [];

        foreach ($this->children as $child) {
            if ($child instanceof FormControl) {
                $rules = array_merge($rules, $child->validationRules($model));
            }
        }

        return $rules;
    }

    protected function applyBeforeSave(Model $model, array $request): Model
    {
        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $model = $child->beforeSave($model, $request);
            }
        }

        return $model;
    }

    protected function applyAfterSave(Model $model, array $request): Model
    {
        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $model = $child->afterSave($model, $request);
            }
        }

        return $model;
    }

    public function searchQuery(Builder $builder, Arrayable|array $request): Builder
    {
        foreach ($this->children as $child) {
            if ($child instanceof SearchForm) {
                $builder = $child->searchQuery($builder, $request);
            }
        }

        return $builder;
    }

    public function afterSearch(Model $model, Arrayable|array $request): Model
    {
        foreach ($this->children as $child) {
            if ($child instanceof SearchForm) {
                $model = $child->afterSearch($model, $request);
            }
        }

        return $model;
    }
}
