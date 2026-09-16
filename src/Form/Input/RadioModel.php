<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\SearchForm;
use Illuminate\Database\Eloquent\Model;

class RadioModel extends SelectModel implements Form, SearchForm
{
    protected bool $labels_group = true;

    public function renderInline(Model $values): string
    {
        $value = $this->getValue($values);
        $value = $this->regulateValue($value);

        $html = '';

        // 選ぶ欄は入力が複数あるので、一つひとつではなく囲みに伝える。
        // aria-required が効くのは radiogroup で、group では読み飛ばされる
        $html .= sprintf(
            '<div role="radiogroup"%s%s>',
            $this->labelledByAttribute(),
            $this->isRequired($values) ? ' aria-required="true"' : '',
        );
        foreach ($this->parseOptions() as $model_value => $model_label) {
            $html .= sprintf(
                '<label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="%s" value="%s"%s><span class="form-check-label" >%s</span></label>',
                e($this->key),
                e($model_value),
                ($model_value === $value ? ' checked' : ''),
                e($model_label),
            );
        }
        $html .= '</div>';

        return $html;
    }
}
