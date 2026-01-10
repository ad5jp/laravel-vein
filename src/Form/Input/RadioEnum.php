<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Illuminate\Database\Eloquent\Model;

class RadioEnum extends SelectEnum implements Form
{
    public function renderInline(?Model $values = null): string
    {
        $value = $values ? $values->{$this->key} : $this->default;
        if ($value && !($value instanceof $this->enum)) {
            $value = ($this->enum)::tryFrom($value);
        }

        $html = '';

        $html .= '<div>';
        foreach ($this->parseOptions() as $enum_value => $enum_label) {
            $html .= sprintf(
                '<label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="%s" value="%s"%s><span class="form-check-label" >%s</span></label>',
                e($this->key),
                e($enum_value),
                ($enum_value === $value?->value ? ' checked' : ''),
                e($enum_label),
            );
        }
        $html .= '</div>';

        return $html;
    }
}
