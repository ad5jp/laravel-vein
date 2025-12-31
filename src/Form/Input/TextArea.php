<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\Helpers\InputHelper;
use Illuminate\Database\Eloquent\Model;

class TextArea implements Input
{
    use InputHelper;

    public function __construct(
        public int $rows = 5,
    ) {
    }

    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;

        $output = '';

        if ($label) {
            $output .= sprintf('<label class="form-label">%s</label>', e($label));
        }
        $output .= sprintf(
            '<textarea name="%s" class="form-control" rows="%s">%s</textarea>',
            e($key),
            e($this->rows),
            e($value),
        );

        return $output;
    }
}
