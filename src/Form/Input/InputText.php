<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use Illuminate\Database\Eloquent\Model;

class InputText implements Input
{
    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;

        return sprintf(
            '<label class="form-label">%s</label><input type="text" name="%s" value="%s" class="form-control">',
            e($label),
            e($key),
            e($value),
        );
    }
}
