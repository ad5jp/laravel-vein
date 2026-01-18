<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Contracts;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Node\Attributes\ListField;
use Illuminate\Database\Eloquent\Builder;

interface Entry extends RootNode
{
    /**
     * @return array<ListField|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:bool}>
     */
    public function listFields(): array;

    /**
     * @return array<Form|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string}>
     */
    public function searchFields(): array;

    public function listOrderDefault(Builder $builder): Builder;

    public function listItemPerPage(): int;

    /**
     * @return array<Form|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string}>
     */
    public function editFields(): array;

    public function editValidatorRules(): array;

    public function editValidatorMessages(): array;

    public function editValidatorAttributes(): array;
}
