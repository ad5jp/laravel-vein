<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface SearchForm
{
    public function renderColumn(Model $values): string;

    public function renderInline(Model $values): string;

    public function afterSearch(Model $values, Arrayable|array $request): Model;

    public function searchQuery(Builder $builder, Arrayable|array $request): Builder;
}
