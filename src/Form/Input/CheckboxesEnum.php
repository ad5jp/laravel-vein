<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Concerns\ResolvesRelations;
use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\LabelledEnum;
use BackedEnum;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckboxesEnum extends FormControl implements Form
{
    use ResolvesRelations;

    public function __construct(
        public string $key,
        public string $enum,
        public ?string $label = null,
        public mixed $default = null,
        public int $colSize = 12,
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
        $value = array_map(fn ($v) => $v instanceof BackedEnum ? $v->value : $v, $value);

        $html = '';

        // 選ぶ欄は入力が複数あるので、囲みに名前を付けて 1 つのまとまりにする。
        // aria-required は group では効かないため付けない（印はラベルに出る）
        $html .= sprintf('<div role="group"%s>', $this->labelledByAttribute());
        foreach ($this->parseOptions() as $enum_value => $enum_label) {
            $html .= sprintf(
                '<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="%s[]" value="%s"%s><span class="form-check-label" >%s</span></label>',
                e($this->key),
                e($enum_value),
                (in_array($enum_value, $value) ? ' checked' : ''),
                e($enum_label),
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

        // リクエストの取得（Enum配列に変換）
        $request_values = $request[$this->key] ?? [];

        if (! is_array($request_values)) {
            throw new Exception('invalid request value for CheckboxesEnum '.$this->key);
        }

        $request_values = array_map(function ($value) {
            if (is_numeric($value)) {
                $value = (int) $value;
            }

            return ($this->enum)::from($value);
        }, $request_values);

        // 既存の値を取得（Enum配列に変換）
        $existing_values = $model->$relation_name->pluck($saving_field)->all();

        // 増えた分レコード追加
        $creating_values = array_udiff($request_values, $existing_values, fn ($a, $b) => $a->value <=> $b->value);
        foreach ($creating_values as $creating_value) {
            $child = $child_model->newInstance();
            $child->$child_key = $model->getKey();
            $child->$saving_field = $creating_value;
            $child->save();
        }

        // 消えたレコードを削除
        $missing_values = array_udiff($existing_values, $request_values, fn ($a, $b) => $a->value <=> $b->value);
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
    private function parseOptions(): array
    {
        $values = array_map(fn (BackedEnum $enum) => $enum->value, ($this->enum)::cases());

        if (is_subclass_of($this->enum, LabelledEnum::class)) {
            $labels = array_map(fn (LabelledEnum $enum) => $enum->label(), ($this->enum)::cases());
        } else {
            $labels = array_map(fn (BackedEnum $enum) => $enum->name, ($this->enum)::cases());
        }

        return array_combine($values, $labels);
    }

    /**
     * @return array{0: string, 1:string}
     */
}
