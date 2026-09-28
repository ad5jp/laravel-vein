<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use AD5jp\Vein\Form\Input\Records;

/**
 * 子レコードの中で描かれるとき、親のキーと添字を受け取れる入力。
 *
 * 検証のキーは officers.0.name の形になる。束ねる側（Row / Group）は受け取った
 * 接頭辞を子へそのまま渡し、どの行のどの欄が弾かれたのかを欄のそばに出せるようにする。
 *
 * @see Records::renderFields()
 */
interface ScopesErrorKeys
{
    public function withErrorKeyPrefix(?string $prefix): static;
}
