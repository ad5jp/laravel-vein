<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\SearchForm;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class InputDate extends FormControl implements Form, SearchForm
{
    protected bool $labelled_input = true;

    public function renderInline(Model $values): string
    {
        $value = $this->getValue($values);

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $html = sprintf(
            '<input type="date" name="%s" value="%s" class="form-control"%s>',
            e($this->key),
            e($value),
            $this->inputAttributes($values),
        );

        return $html;
    }
}
