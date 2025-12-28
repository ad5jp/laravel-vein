<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use Illuminate\Database\Eloquent\Model;

class TextArea implements Input
{
    public function __construct(
        public int $rows = 5,
    ) {
    }

    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;

        return sprintf(
            '<label class="form-label">%s</label><textarea name="%s" class="form-control" rows="%s">%s</textarea>',
            e($label),
            e($key),
            e($this->rows),
            e($value),
        );
    }
}
