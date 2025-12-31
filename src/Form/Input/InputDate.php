<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\Helpers\InputHelper;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class InputDate implements Input
{
    use InputHelper;

    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $output = '';

        if ($label) {
            $output .= sprintf('<label class="form-label">%s</label>', e($label));
        }
        $output .= sprintf(
            '<input type="date" name="%s" value="%s" class="form-control">',
            e($key),
            e($value),
        );

        return $output;
    }
}
