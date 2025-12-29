<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\Contracts\LabelledEnum;
use BackedEnum;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SelectEnum implements Input
{
    public function __construct(public string $enum)
    {
        if (!enum_exists($enum)) {
            throw new Exception("{$enum} は Enum ではありません");
        }
        if (!is_subclass_of($enum, BackedEnum::class)) {
            throw new Exception("{$enum} は BackedEnum ではありません");
        }
    }

    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;
        if ($value && !($value instanceof $this->enum)) {
            $value = ($this->enum)::tryFrom($value);
        }

        $output = '';

        if ($label) {
            $output .= sprintf('<label class="form-label">%s</label>', e($label));
        }
        $output .= sprintf('<select name="%s" class="form-select">', e($key));
        $output .= '<option value="">-- 選択してください --</option>';
        foreach ($this->parseOptions() as $enum_value => $enum_label) {
            $output .= sprintf('<option value="%s"%s>%s</option>', e($enum_value), ($enum_value === $value?->value ? ' selected' : ''), e($enum_label));
        }
        $output .= '</select>';

        return $output;
    }

    /**
     * @return array{int|string, string}
     */
    public function parseOptions(): array
    {
        $values = array_map(fn (BackedEnum $enum) => $enum->value, ($this->enum)::cases());

        if (is_subclass_of($this->enum, LabelledEnum::class)) {
            $labels = array_map(fn (LabelledEnum $enum) => $enum->label(), ($this->enum)::cases());
        } else {
            $labels = array_map(fn (BackedEnum $enum) => $enum->name, ($this->enum)::cases());
        }

        return array_combine($values, $labels);
    }
}
