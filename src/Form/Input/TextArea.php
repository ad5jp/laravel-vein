<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Closure;
use Illuminate\Database\Eloquent\Model;

class TextArea extends FormControl implements Form
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 12,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
        public ?string $placeholder = null,
        public int $rows = 5,
    ) {
        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching, $placeholder);
    }

    public function renderInline(Model $values): string
    {
        $value = $this->getValue($values);

        $html = sprintf(
            '<textarea name="%s" class="form-control" rows="%s"%s>%s</textarea>',
            e($this->key),
            e($this->rows),
            $this->placeholderAttribute(),
            e($value),
        );

        return $html;
    }
}
