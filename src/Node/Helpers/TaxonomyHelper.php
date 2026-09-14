<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Helpers;

trait TaxonomyHelper
{
    public function menuName(): string
    {
        $class_name_parts = explode('\\', self::class);

        return $class_name_parts[array_key_last($class_name_parts)];
    }

    public function menuOrder(): int
    {
        return 300;
    }

    public function menuIcon(): ?string
    {
        return 'list-ul';
    }

    public function orderColumn(): ?string
    {
        return null;
    }

    public function editValidatorRules(): array
    {
        return [];
    }

    public function editValidatorMessages(): array
    {
        return [];
    }

    public function editValidatorAttributes(): array
    {
        return [];
    }
}
