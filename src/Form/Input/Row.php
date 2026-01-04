<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Row implements Form
{
    public function __construct(
        /** @var Form[] */
        public array $children = [],
    ) {

    }

    public function render(?Model $model = null): string
    {
        $html = '';
        $html .= '<div class="row mb-3">';

        FormControl::startRow();

        foreach ($this->children as $child) {
            $html .= $child->render($model);
        }

        FormControl::endRow();

        $html .= '</div>';

        return $html;
    }

    public function beforeSave(Model $model, Request $request): Model
    {
        foreach ($this->children as $child) {
            $model = $child->beforeSave($model, $request);
        }

        return $model;
    }

    public function afterSave(Model $model, Request $request): Model
    {
        foreach ($this->children as $child) {
            $model = $child->afterSave($model, $request);
        }

        return $model;
    }

    public function searchQuery(Builder $builder, Request $request): Builder
    {
        foreach ($this->children as $child) {
            $builder = $child->searchQuery($builder, $request);
        }

        return $builder;
    }
}
