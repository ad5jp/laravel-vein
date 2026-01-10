<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class InputNumber extends FormControl implements Form
{
    // TODO Prefix/Suffix をつける（Groupとの関係に注意）

    public function renderInline(?Model $values = null): string
    {
        $value = $values ? $values->{$this->key} : $this->default;

        $html = sprintf(
            '<input type="number" name="%s" value="%s" class="form-control">',
            e($this->key),
            e($value),
        );

        return $html;
    }
}
