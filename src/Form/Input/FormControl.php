<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class FormControl
{
    private static $inRow = false;
    private static $inCol = false;
    private static $inGroup = false;

    public function __construct(
        public string $key,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 12,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
    ) {
    }

    public function beforeSave(Model $model, Request $request): Model
    {
        $model->{$this->key} = $request->{$this->key};
        return $model;
    }

    public function afterSave(Model $model, Request $request): Model
    {
        // DO NOTHING
        return $model;
    }

    public function searchQuery(Builder $builder, Request $request): Builder
    {
        return $builder->where($this->key, $request->input($this->key));
    }

    protected function wrap(string $html): string
    {
        if (self::$inGroup === false) {
            if ($this->label) {
                $html = sprintf('<label class="form-label">%s</label>%s', e($this->label), $html);
            }
        }

        if (self::$inCol === false) {
            $medium = $this->colSize;
            $medium = $medium > 12 ? 12 : $medium;

            $small = $this->colSize * 2;
            $small = $small > 12 ? 12 : $small;

            $html = sprintf(
                '<div class="col-md-%s col-sm-%s col-12">%s</div>',
                $medium,
                $small,
                $html,
            );
        }

        if (self::$inRow === false) {
            $html = sprintf(
                '<div class="row mb-3">%s</div>',
                $html,
            );
        }

        return $html;
    }

    public static function startRow(): void
    {
        self::$inRow = true;
    }

    public static function endRow(): void
    {
        self::$inRow = false;
    }

    public static function inRow(): bool
    {
        return self::$inRow;
    }

    public static function startCol(): void
    {
        self::$inCol = true;
    }

    public static function endCol(): void
    {
        self::$inCol = false;
    }

    public static function inCol(): bool
    {
        return self::$inCol;
    }

    public static function startGroup(): void
    {
        self::$inGroup = true;
    }

    public static function endGroup(): void
    {
        self::$inGroup = false;
    }

    public static function inGroup(): bool
    {
        return self::$inGroup;
    }
}
