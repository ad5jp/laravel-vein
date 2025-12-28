<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Input
{
    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string;
}
