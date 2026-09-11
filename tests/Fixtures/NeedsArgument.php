<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * 引数の要るコンストラクタを持つモデル。Node としては扱えない。
 */
class NeedsArgument extends Model
{
    public function __construct(string $required)
    {
        parent::__construct([]);
    }
}
