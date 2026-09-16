<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\SearchForm;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;

class InputNumber extends FormControl implements Form, SearchForm
{
    // TODO Prefix/Suffix をつける（Groupとの関係に注意）

    public function __construct(
        public string $key,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 4,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
        public int|float|string|null $step = null,
    ) {
        if ($step !== null) {
            if (! is_numeric($step) && $step !== 'any') {
                throw new Exception("step には正の数か any を指定してください（{$step}）");
            }
            if (is_numeric($step) && (float) $step <= 0) {
                throw new Exception("step には正の数を指定してください（{$step}）");
            }
        }

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    protected bool $labelled_input = true;

    public function renderInline(Model $values): string
    {
        $value = $this->getValue($values);

        $html = sprintf(
            '<input type="number"%s name="%s" value="%s" class="form-control"%s>',
            $this->renderStep(),
            e($this->key),
            e($value),
            $this->inputAttributes($values),
        );

        return $html;
    }

    /**
     * step 属性を組み立てる。
     *
     * 省略するとブラウザ既定の step="1" が効き、小数の入力が弾かれる。
     */
    protected function renderStep(): string
    {
        if ($this->step === null) {
            return '';
        }

        return sprintf(' step="%s"', e((string) $this->step));
    }
}
