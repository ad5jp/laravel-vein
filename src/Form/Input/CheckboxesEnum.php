<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Input;
use AD5jp\Vein\Form\Contracts\LabelledEnum;
use BackedEnum;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckboxesEnum implements Input
{
    public function __construct(public string $enum)
    {
        if (!enum_exists($enum)) {
            throw new Exception("{$enum} は Enum ではありません");
        }
        if (!is_subclass_of($enum, BackedEnum::class)) {
            throw new Exception("{$enum} は BackedEnum ではありません");
        }
    }

    public function render(?Model $values, string $key, ?string $label, mixed $default = []): string
    {
        list($relation_name, $saving_field) = $this->parseKey($values, $key);

        $value = is_array($default) ? $default : [$default];
        if ($values) {
            $value = $values->$relation_name->map(fn (Model $related) => $related->$saving_field)->all();
        }
        $value = array_map(function ($v) {
            return $v instanceof BackedEnum ? $v->value : $v;
        }, $value);

        $output = '';

        if ($label) {
            $output .= sprintf('<label class="form-label">%s</label>', e($label));
        }
        $output .= '<div>';
        foreach ($this->parseOptions() as $enum_value => $enum_label) {
            $output .= sprintf(
                '<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="%s[]" value="%s"%s><span class="form-check-label" >%s</span></label>',
                e($key),
                e($enum_value),
                (in_array($enum_value, $value) ? ' checked' : ''),
                e($enum_label),
            );
        }
        $output .= '</div>';

        return $output;
    }

    public function beforeSave(Model $model, string $key, Request $request): Model
    {
        // DO NOTHING
        return $model;
    }

    public function afterSave(Model $model, string $key, Request $request): Model
    {
        // リレーションを差分更新する

        // キーの検査
        list($relation_name, $saving_field) = $this->parseKey($model, $key);

        // リレーションオブジェクトを取得
        /** @var HasMany $has_many */
        $has_many = $model->$relation_name();
        $child_model = $has_many->getRelated();
        $child_key = $has_many->getForeignKeyName();

        // リクエストの取得（Enum配列に変換）
        $request_values = $request->input($key, []);

        if (!is_array($request_values)) {
            throw new Exception('invalid request value for CheckboxesEnum ' . $key);
        }

        $request_values = array_map(function ($value) {
            if (is_numeric($value)) {
                $value = (int)$value;
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
    private function parseKey(?Model $model, string $key): array
    {
        $segments = explode(':', $key);

        if (count($segments) !== 2) {
            throw new Exception('CheckboxesEnum の key ' . $key . ' の形式が不正です（relation_name:saving_field）');
        }

        list($relation_name, $saving_field) = $segments;

        if ($model === null) {
            return [$relation_name, $saving_field];
        }

        foreach ([$relation_name, Str::camel($relation_name)] as $relation_method_name) {
            if (method_exists($model, $relation_method_name)) {
                $relation = $model->$relation_method_name();
                if ($relation instanceof HasMany) {
                    return [$relation_name, $saving_field];
                } else {
                    throw new Exception('Model ' . get_class($model) . ' の ' . $relation_name . '() は HasMany リレーションではありません');
                }
            }
        }

        throw new Exception('Model ' . get_class($model) . ' にリレーション ' . $relation_name . ' が定義されていません');
    }
}
