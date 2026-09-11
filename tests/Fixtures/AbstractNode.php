<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Helpers\EntryHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * 共通の親として置かれた abstract クラス。
 *
 * class_exists() は true を返すので、判定より先に new すると Error になる。
 */
abstract class AbstractNode extends Model implements Entry
{
    use EntryHelper;

    public function listFields(): array
    {
        return [];
    }

    public function editFields(): array
    {
        return [];
    }
}
