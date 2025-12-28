<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Validator;

trait EntryHelper
{
    public function menuName(): string
    {
        $class_name_parts = explode('\\', self::class);
        return $class_name_parts[array_key_last($class_name_parts)];
    }

    public function menuOrder(): int
    {
        return 100;
    }

    /**
     * @return SearchField[]
     */
    public function listSearch(): array
    {
        return [];
    }

    public function listOrderDefault(Builder $builder): Builder
    {
        return $builder->orderBy($this->getKeyName(), 'desc');
    }

    public function listItemPerPage(): int
    {
        return 20;
    }

    public function editValidator(): ?Validator
    {
        return null;
    }
}
