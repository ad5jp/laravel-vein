<?php

declare(strict_types=1);

namespace AD5jp\Vein\Navigation;

class Nav
{
    public string $label;

    public ?string $link;

    public int $order;

    public array $children = [];
}
