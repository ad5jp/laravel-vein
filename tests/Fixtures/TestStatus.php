<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

/**
 * モデルと同じディレクトリに置かれた enum。
 */
enum TestStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
