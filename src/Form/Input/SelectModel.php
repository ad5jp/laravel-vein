<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use BackedEnum;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SelectModel implements Input
{
    public function __construct(
        public string $model,
        public string $modelLabel,
        public string|Closure|null $modelOrder = null,
        public array|Closure|null $modelWhere = null,
    ) {
        if (!class_exists($model) || !is_subclass_of($model, Model::class)) {
            throw new Exception("Model {$model} が存在しません");
        }
    }

    public function render(?Model $values, string $key, ?string $label, mixed $default = null): string
    {
        $value = $values ? $values->$key : $default;

        $output = '';

        if ($label) {
            $output .= sprintf('<label class="form-label">%s</label>', e($label));
        }
        $output .= sprintf('<select name="%s" class="form-select">', e($key));
        $output .= '<option value="">-- 選択してください --</option>';
        foreach ($this->parseOptions() as $model_value => $model_label) {
            $output .= sprintf('<option value="%s"%s>%s</option>', e($model_value), ($model_value === $value ? ' selected' : ''), e($model_label));
        }
        $output .= '</select>';

        return $output;
    }

    /**
     * @return array{int|string, string}
     */
    public function parseOptions(): array
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
