<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Concerns\ResolvesRelations;
use AD5jp\Vein\Form\Contracts\Form;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckboxesModel extends FormControl implements Form
{
    use ResolvesRelations;

    public function __construct(
        public string $key,
        public string $model,
        public string $modelLabel,
        public string|Closure|null $modelOrder = null,
        public array|Closure|null $modelWhere = null,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 12,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
    ) {
        if (! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            throw new Exception("Model {$model} が存在しません");
        }

        if ($default === null) {
            $default = [];
        } elseif (! is_array($default)) {
            $default = [$default];
        }

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    public function renderInline(Model $values): string
    {
        [$relation_name, $saving_field] = $this->parseRelationKey($values, $this->key);

        $value = $values->$relation_name->map(fn (Model $related) => $related->$saving_field)->all() ?: $this->default;
        $value = old($this->key, $value);
        $value = array_map($this->regulateValue(...), $value);

        $html = '';

        // 選ぶ欄は入力が複数あるので、一つひとつではなく囲みに必須を伝える
        $html .= sprintf(
            '<div role="group"%s>',
            $this->isRequired($values) ? ' aria-required="true"' : '',
        );
        foreach ($this->parseOptions() as $model_key => $model_label) {
            $html .= sprintf(
                '<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="%s[]" value="%s"%s><span class="form-check-label" >%s</span></label>',
                e($this->key),
                e($model_key),
                (in_array($model_key, $value) ? ' checked' : ''),
                e($model_label),
            );
        }
        $html .= '</div>';

        return $html;
    }

    protected function applyBeforeSave(Model $model, array $request): Model
    {
        // DO NOTHING
        return $model;
    }

    protected function applyAfterSave(Model $model, array $request): Model
    {
        // リレーションを差分更新する

        // キーの検査
        [$relation_name, $saving_field] = $this->parseRelationKey($model, $this->key);

        // リレーションオブジェクトを取得
        /** @var HasMany $has_many */
        $has_many = $model->$relation_name();
        $child_model = $has_many->getRelated();
        $child_key = $has_many->getForeignKeyName();

        // リクエストの取得
        $request_values = $request[$this->key] ?? [];

        if (! is_array($request_values)) {
            throw new Exception('invalid request value for CheckboxesEnum '.$this->key);
        }

        // 既存の値を取得
        $existing_values = $model->$relation_name->pluck($saving_field)->all();

        // 増えた分レコード追加
        $creating_values = array_diff($request_values, $existing_values);
        foreach ($creating_values as $creating_value) {
            $child = $child_model->newInstance();
            $child->$child_key = $model->getKey();
            $child->$saving_field = $creating_value;
            $child->save();
        }

        // 消えたレコードを削除
        $missing_values = array_diff($existing_values, $request_values);
        if (count($missing_values) > 0) {
            $model->$relation_name()
                ->whereIn($saving_field, $missing_values)
                ->delete();
        }

        return $model;
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

    /**
     * @return array{0: string, 1:string}
     */
    protected function regulateValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ((new ($this->model)())->getKeyType() === 'string') {
            return (string) $value;
        }

        return (int) $value;
    }
}
