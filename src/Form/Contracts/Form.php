<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface Form
{
    public function render(?Model $values = null): string;

    public function beforeSave(Model $values, Request $request): Model;

    public function afterSave(Model $values, Request $request): Model;

    public function searchQuery(Builder $builder, Request $request): Builder;
}
