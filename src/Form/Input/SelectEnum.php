<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\LabelledEnum;
use AD5jp\Vein\Form\Contracts\SearchForm;
use BackedEnum;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use ReflectionEnum;

class SelectEnum extends FormControl implements Form, SearchForm
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
        if (! enum_exists($enum)) {
            throw new Exception("{$enum} は Enum ではありません");
        }
        if (! is_subclass_of($enum, BackedEnum::class)) {
            throw new Exception("{$enum} は BackedEnum ではありません");
        }

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    protected bool $labelled_input = true;

    public function renderInline(Model $values): string
    {
        $value = $this->getValue($values);
        $value = $this->regulateValue($value);

        $html = '';

        $html .= sprintf('<select name="%s" class="form-select"%s>', e($this->key), $this->inputAttributes());
        $html .= '<option value="">-- 選択してください --</option>';
        foreach ($this->parseOptions() as $enum_value => $enum_label) {
            $html .= sprintf('<option value="%s"%s>%s</option>', e($enum_value), ($enum_value === $value?->value ? ' selected' : ''), e($enum_label));
        }
        $html .= '</select>';

        return $html;
    }

    protected function applyBeforeSave(Model $model, array $request): Model
    {
        $value = $request[$this->key] ?? null;

        if ($value !== null) {
            if (is_numeric($value)) {
                $value = (int) $value;
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

    protected function regulateValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enum) {
            return $value;
        }

        $ref = new ReflectionEnum($this->enum);
        if ($ref->getBackingType()->getName() === 'int') {
            return ($this->enum)::tryFrom((int) $value);
        }

        return ($this->enum)::tryFrom((string) $value);
    }
}
