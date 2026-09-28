<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Helpers;

trait SortableHelper
{
    public function sortColumn(): string
    {
        return 'sort_order';
    }

    public function sortGroupColumn(): ?string
    {
        return null;
    }

    public function sortGroups(): array
    {
        return [];
    }

    public function sortItemLabel(): string
    {
        return (string) $this->getKey();
    }
}
