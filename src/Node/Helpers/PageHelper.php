<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Helpers;

trait PageHelper
{
    public function menuName(): string
    {
        $class_name_parts = explode('\\', self::class);

        return $class_name_parts[array_key_last($class_name_parts)];
    }

    public function menuOrder(): int
    {
        return 200;
    }

    public function menuIcon(): ?string
    {
        return 'file-richtext';
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
