<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Attributes;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\InputManager;
use Exception;
use Illuminate\Database\Eloquent\Model;

class EditField
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public Input|string|null $input = null,
        public mixed $default = null,
        public int $column_size = 6,
    ) {

    }

    public function render(?Model $values = null): string
    {
        // 画面上に表示しない項目
        if ($this->input === null) {
            return '';
        }

        if (is_string($this->input)) {
            $input_manager = new InputManager();
            $this->input = $input_manager->resolve($this->input);
        }

        return $this->input->render($values, $this->key, $this->label, $this->default);
    }

    public function columnClass(): string
    {
        $medium = $this->column_size;
        $medium = $medium > 12 ? 12 : $medium;

        $small = $this->column_size * 2;
        $small = $small > 12 ? 12 : $small;

        return "col-md-{$medium} col-sm-{$small} col-12";
    }

    /**
     * @param array<EditField|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:bool}> $editFields
     * @return EditField[]
     */
    public static function parse(array $editFields): array
    {
        return array_map(function ($editField) {
            if ($editField instanceof EditField) {
                return $editField;
            }

            if (is_array($editField)) {
                return EditField::fromArray($editField);
            }

            throw new Exception('invalid element for editFields: ' . var_export($editField, true));
        }, $editFields);
    }

    /**
     * @param array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:string} $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $key = $attributes[0];
        $label = $attributes[1] ?? null;
        $input = $attributes[2] ?? null;

        return new EditField($key, $label, $input);
    }
}
