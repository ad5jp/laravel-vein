<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Illuminate\Database\Eloquent\Model;

class InputText extends FormControl implements Form
{
    public function renderInline(?Model $values = null): string
    {
        $value = $values ? $values->{$this->key} : $this->default;

        $html = sprintf(
            '<input type="text" name="%s" value="%s" class="form-control">',
            e($this->key),
            e($value),
        );

        return $html;
    }
}
