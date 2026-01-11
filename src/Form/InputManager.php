<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Input\InputDate;
use AD5jp\Vein\Form\Input\InputNumber;
use AD5jp\Vein\Form\Input\InputText;
use AD5jp\Vein\Form\Input\TextArea;
use Exception;

class InputManager
{
    public function parseEditField(array $editFields): array
    {
        return array_map(function ($editField) {
            if ($editField instanceof Form) {
                return $editField;
            }

            if (is_array($editField)) {
                return $this->resolve($editField);
            }

            throw new Exception('invalid element for editFields: ' . var_export($editField, true));
        }, $editFields);
    }

    /**
     * @param array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string} $attributes
     */
    public function resolve(array $attributes): Form
    {
        $key = $attributes[0];
        $label = $attributes[1] ?? null;
        $input = $attributes[2] ?? 'text';

        return match ($input) {
            'text' => new InputText(key: $key, label: $label),
            'date' => new InputDate(key: $key, label: $label),
            'number' => new InputNumber(key: $key, label: $label),
            'textarea' => new TextArea(key: $key, label: $label),
            default => throw new Exception('invalid input: ' . $input),
        };
    }
}
