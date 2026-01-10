<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Group extends FormControl implements Form
{
    public function __construct(
        public ?string $label = null,
        public int $colSize = 12,
        /** @var array<int, Form|string> */
        public array $children = [],
    ) {
        parent::__construct('__', $label, null, $colSize);
    }

    public function renderInline(?Model $model = null): string
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

    public function beforeSave(Model $model, Request $request): Model
    {
        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $model = $child->beforeSave($model, $request);
            }
        }

        return $model;
    }

    public function afterSave(Model $model, Request $request): Model
    {
        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $model = $child->afterSave($model, $request);
            }
        }

        return $model;
    }

    public function searchQuery(Builder $builder, Request $request): Builder
    {
        foreach ($this->children as $child) {
            if ($child instanceof Form) {
                $builder = $child->searchQuery($builder, $request);
            }
        }

        return $builder;
    }
}
