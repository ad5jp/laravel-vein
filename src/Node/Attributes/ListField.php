<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Attributes;

use AD5jp\Vein\Form\Contracts\LabelledEnum;
use BackedEnum;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListField
{
    public function __construct(
        /**
         * @var string|Closure(Model): string
         */
        public string|Closure $value,
        public string $label,
        public bool $sortable = true,
        public ?Builder $sort_asc = null,
        public ?Builder $sort_desc = null,
    ) {

    }

    public function getValue(Model $model): string
    {
        if ($this->value instanceof Closure) {
            return ($this->value)($model);
        }

        $value = $model->{$this->value};

        if ($value instanceof LabelledEnum) {
            return $value->label();
        } elseif ($value instanceof BackedEnum) {
            return $value->name;
        }

        return (string)$value;
    }

    /**
     * @param array<ListField|array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:bool}> $listFields
     * @return ListField[]
     */
    public static function parse(array $listFields): array
    {
        return array_map(function ($listField) {
            if ($listField instanceof ListField) {
                return $listField;
            }

            if (is_array($listField)) {
                return ListField::fromArray($listField);
            }

            throw new Exception('invalid element for listFields: ' . var_export($listField, true));
        }, $listFields);
    }

    /**
     * @param array{0:string}|array{0:string, 1:string}|array{0:string, 1:string, 2:bool} $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $value = $attributes[0];
        $label = $attributes[1] ?? $attributes[0];
        $sortable = $attributes[2] ?? true;

        return new ListField($value, $label, $sortable);
    }
}
