<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Form\Contracts\LabelledEnum;

/**
 * モデルと同じディレクトリに置かれた enum。
 */
enum TestStatus: string implements LabelledEnum
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => '下書き',
            self::Published => '公開',
        };
    }
}
