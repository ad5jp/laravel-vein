<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface Input
{
    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string;

    public function beforeSave(Model $values, string $key, Request $request): Model;

    public function afterSave(Model $values, string $key, Request $request): Model;
}
