<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\ScopesErrorKeys;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class Row implements Form, ScopesErrorKeys
{
    public function __construct(
        /** @var Form[] */
        public array $children = [],
    ) {}

    public function render(Model $model): string
    {
        $html = '';
        $html .= '<div class="row mb-3">';

        foreach ($this->children as $child) {
            $html .= $child->renderColumn($model);
        }

        $html .= '</div>';

        return $html;
    }

    public function renderColumn(Model $model): string
    {
        throw new Exception('Row cannot be rendered as Column');
    }

    /**
     * 行そのものは値を持たないので、受け取った接頭辞は子へそのまま渡す。
     *
     * これをしないと、子レコードの中で Row に束ねた欄が弾かれたとき、理由が
     * 画面の上のまとめにしか出ない。
     */
    public function withErrorKeyPrefix(?string $prefix): static
    {
        foreach ($this->children as $child) {
            if ($child instanceof ScopesErrorKeys) {
                $child->withErrorKeyPrefix($prefix);
            }
        }

        return $this;
    }

    public function renderInline(Model $model): string
    {
        throw new Exception('Row cannot be rendered inline');
    }

    /**
     * @return array<string, mixed>
     */
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

    public function beforeSave(Model $model, Arrayable|array $request): Model
    {
        foreach ($this->children as $child) {
            $model = $child->beforeSave($model, $request);
        }

        return $model;
    }

    public function afterSave(Model $model, Arrayable|array $request): Model
    {
        foreach ($this->children as $child) {
            $model = $child->afterSave($model, $request);
        }

        return $model;
    }
}
