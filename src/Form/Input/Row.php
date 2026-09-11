<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class Row implements Form
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
