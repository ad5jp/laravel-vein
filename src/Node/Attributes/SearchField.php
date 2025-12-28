<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Attributes;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\InputManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchField
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public Input|string $input = 'text',
        /**
         * @var ?Closure(Builder, mixed):Builder
         */
        public ?Closure $where = null,
        public mixed $default = null,
        public ?int $column_size = null,
    ) {

    }

    // TODO Model ではなく Request が渡ってくる
    public function render(?Model $values = null): string
    {
        if (is_string($this->input)) {
            $input_manager = new InputManager();
            $this->input = $input_manager->resolve($this->input);
        }

        return $this->input->render($values, $this->key, $this->label, $this->default);
    }

    public function applyWhere(Builder $builder, mixed $value): Builder
    {
        if ($this->where === null) {
            return $builder->where($this->key, $value);
        }

        assert($this->where instanceof Closure);
        return ($this->where)($builder, $value);
    }

    public function columnClass(): string
    {
        if ($this->column_size === null) {
            return "col";
        }

        $medium = $this->column_size;
        $medium = $medium > 12 ? 12 : $medium;

        $small = $this->column_size * 2;
        $small = $small > 12 ? 12 : $small;

        return "col-md-{$medium} col-sm-{$small} col-12";
    }
}
