<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\LabelledEnum;
use BackedEnum;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SelectEnum extends FormControl implements Form
{
    public function __construct(
        public string $key,
        public string $enum,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 4,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
    ) {
        if (!enum_exists($enum)) {
            throw new Exception("{$enum} は Enum ではありません");
        }
        if (!is_subclass_of($enum, BackedEnum::class)) {
            throw new Exception("{$enum} は BackedEnum ではありません");
        }

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    public function render(?Model $values = null): string
    {
        $value = $values ? $values->{$this->key} : $this->default;
        if ($value && !($value instanceof $this->enum)) {
            $value = ($this->enum)::tryFrom($value);
        }

        $html = '';

        $html .= sprintf('<select name="%s" class="form-select">', e($this->key));
        $html .= '<option value="">-- 選択してください --</option>';
        foreach ($this->parseOptions() as $enum_value => $enum_label) {
            $html .= sprintf('<option value="%s"%s>%s</option>', e($enum_value), ($enum_value === $value?->value ? ' selected' : ''), e($enum_label));
        }
        $html .= '</select>';

        return $this->wrap($html);
    }

    public function beforeSave(Model $model, Request $request): Model
    {
        $value = $request->{$this->key};

        if ($value !== null) {
            if (is_numeric($value)) {
                $value = (int)$value;
            }

            $value = ($this->enum)::from($value);
        }

        $model->{$this->key} = $value;
        return $model;
    }

    /**
     * @return array{int|string, string}
     */
    protected function parseOptions(): array
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
