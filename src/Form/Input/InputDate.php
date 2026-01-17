<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\SearchForm;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class InputDate extends FormControl implements Form, SearchForm
{
    public function renderInline(Model $values): string
    {
        $value = $values->{$this->key} ?? $this->default;

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $html = sprintf(
            '<input type="date" name="%s" value="%s" class="form-control">',
            e($this->key),
            e($value),
        );

        return $html;
    }
}
