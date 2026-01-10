<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Illuminate\Database\Eloquent\Model;

class RadioModel extends SelectModel implements Form
{
    public function renderInline(?Model $values = null): string
    {
        $value = $values ? $values->{$this->key} : $this->default;

        $html = '';

        $html .= '<div>';
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
