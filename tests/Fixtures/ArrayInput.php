<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Input\FormControl;
use Illuminate\Database\Eloquent\Model;

/**
 * 配列で送る入力（name="key[]"）の最小形。
 *
 * Records が name を書き換えるとき、[] の付いた名前を壊さないことを確かめるために置いている。
 */
class ArrayInput extends FormControl implements Form
{
    public function render(Model $values): string
    {
        return sprintf('<input type="checkbox" name="%s[]" value="1">', e($this->key));
    }

    public function renderColumn(Model $values): string
    {
        return '';
    }

    public function renderInline(Model $values): string
    {
        return $this->render($values);
    }
}
