<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Contracts;

interface RootNode
{
    public function menuName(): string;

    public function menuOrder(): int;

    public function menuIcon(): ?string;
}
