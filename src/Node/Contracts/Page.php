<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Contracts;

use Illuminate\Validation\Validator;

interface Page
{
    public function menuName(): string;

    public function menuOrder(): int;

    /**
     * @return array<Form|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string}>
     */
    public function editFields(): array;

    public function editValidator(): ?Validator;
}
