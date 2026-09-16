<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

interface Form
{
    public function render(Model $values): string;

    public function renderColumn(Model $values): string;

    public function renderInline(Model $values): string;

    public function beforeSave(Model $values, Arrayable|array $request): Model;

    public function afterSave(Model $values, Arrayable|array $request): Model;
}
