<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\Helpers\InputHelper;
use Illuminate\Database\Eloquent\Model;

class InputText implements Input
{
    use InputHelper;

    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;

        $output = '';

        if ($label) {
            $output .= sprintf('<label class="form-label">%s</label>', e($label));
        }
        $output .= sprintf(
            '<input type="text" name="%s" value="%s" class="form-control">',
            e($key),
            e($value),
        );

        return $output;
    }
}
