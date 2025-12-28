<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\Input\InputDate;
use AD5jp\Vein\Form\Input\InputText;
use AD5jp\Vein\Form\Input\TextArea;
use Exception;

class InputManager
{
    public function resolve(string $input_name): Input
    {
        return match ($input_name) {
            'text' => new InputText(),
            'date' => new InputDate(),
            'textarea' => new TextArea(),
            default => throw new Exception('invalid input: ' . $input_name),
        };
    }
}
