<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Contracts;

interface Page extends RootNode
{
    /**
     * @return array<Form|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string}>
     */
    public function editFields(): array;

    public function editValidatorRules(): array;

    public function editValidatorMessages(): array;

    public function editValidatorAttributes(): array;
}
