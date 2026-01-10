<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
    ) {
    }

    public function beforeSave(Model $model, Request $request): Model
    {
        $model->{$this->key} = $request->{$this->key};
        return $model;
    }

    public function afterSave(Model $model, Request $request): Model
    {
        // DO NOTHING
        return $model;
    }

    public function searchQuery(Builder $builder, Request $request): Builder
    {
        return $builder->where($this->key, $request->input($this->key));
    }

    public function render(?Model $values = null): string
    {
        return sprintf(
            '<div class="row mb-3">%s</div>',
            $this->renderColumn($values),
        );
    }

    public function renderColumn(?Model $values = null): string
    {
        $html = $this->renderInline($values);

        if ($this->label) {
            $html = sprintf('<label class="form-label">%s</label>%s', e($this->label), $html);
        }

        $medium = $this->colSize;
        $medium = $medium > 12 ? 12 : $medium;

        $small = $this->colSize * 2;
        $small = $small > 12 ? 12 : $small;

        $html = sprintf(
            '<div class="col-md-%s col-sm-%s col-12">%s</div>',
            $medium,
            $small,
            $html,
        );

        return $html;
    }

    public abstract function renderInline(?Model $values = null): string;
}
