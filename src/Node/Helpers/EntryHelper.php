<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Helpers;

use Illuminate\Database\Eloquent\Builder;

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

    public function menuIcon(): ?string
    {
        return 'journal-text';
    }

    /**
     * @return array<Form|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string}>
     */
    public function searchFields(): array
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

    /**
     * 検証にかける前に、送られてきた値を整える。
     *
     * 大小の揺れのように「弾くより直したほうがよい」ものをここで揃える。
     * 整えた値は検証にも保存にも使われる。
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editValidatorPrepare(array $input): array
    {
        return $input;
    }
}
