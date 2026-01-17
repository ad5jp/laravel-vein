<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\SearchForm;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
                $html .= sprintf('<span class="input-group-text">%s</span>', $child);
            } else {
                throw new Exception('Invalid children of Group');
            }
        }

        $html .= '</div>';

        return $html;
    }

    public function beforeSave(Model $model, Arrayable|array $request): Model
    {
        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $model = $child->beforeSave($model, $request);
            }
        }

        return $model;
    }

    public function afterSave(Model $model, Arrayable|array $request): Model
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
