<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\SearchForm;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InputText extends FormControl implements Form, SearchForm
{
    protected bool $labelled_input = true;

    public function renderInline(Model $values): string
    {
        $value = $this->getValue($values);

        $html = sprintf(
            '<input type="text" name="%s" value="%s" class="form-control"%s>',
            e($this->key),
            e($value),
            $this->placeholderAttribute().$this->inputAttributes(),
        );

        return $html;
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

        return $builder->where($this->key, 'like', "%$value%");
    }
}
