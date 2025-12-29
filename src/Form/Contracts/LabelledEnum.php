<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Database\Eloquent\Model;

interface LabelledEnum
{
    public function label(): string;
}
