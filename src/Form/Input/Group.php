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

        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $html .= $child->renderInline($model);
            } elseif (is_string($child)) {
                $html .= sprintf('<span class="input-group-text">%s</span>', e($child));
            } else {
                throw new Exception('Invalid children of Group');
            }
        }

        $html .= '</div>';

        return $html;
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
