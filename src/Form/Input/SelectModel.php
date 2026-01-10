<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SelectModel extends FormControl implements Form
{
    public function __construct(
        public string $key,
        public string $model,
        public string $modelLabel,
        public string|Closure|null $modelOrder = null,
        public array|Closure|null $modelWhere = null,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 4,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
    ) {
        if (!class_exists($model) || !is_subclass_of($model, Model::class)) {
            throw new Exception("Model {$model} が存在しません");
        }

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    public function renderInline(?Model $values = null): string
    {
        $value = $values ? $values->{$this->key} : $this->default;

        $html = '';

        $html .= sprintf('<select name="%s" class="form-select">', e($this->key));
        $html .= '<option value="">-- 選択してください --</option>';
        foreach ($this->parseOptions() as $model_value => $model_label) {
            $html .= sprintf('<option value="%s"%s>%s</option>', e($model_value), ($model_value === $value ? ' selected' : ''), e($model_label));
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @return array{int|string, string}
     */
    protected function parseOptions(): array
    {
        $query = ($this->model)::query();

        if (is_string($this->modelOrder)) {
            $query->orderBy($this->modelOrder, 'asc');
        } elseif ($this->modelOrder instanceof Closure) {
            $query = ($this->modelOrder)($query);
        }

        if (is_array($this->modelWhere)) {
            $query->where(...$this->modelWhere);
        } elseif ($this->modelWhere instanceof Closure) {
            $query = ($this->modelWhere)($query);
        }

        return $query->get()->mapWithKeys(function (Model $model) {
            return [$model->getKey() => $model->{$this->modelLabel}];
        })->all();
    }
}
