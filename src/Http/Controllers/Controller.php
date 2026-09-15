<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * 各フォーム要素が要求するバリデーション規則を集める。
     *
     * @param  array<int, mixed>  $editFields
     * @return array<string, mixed>
     */
    protected function rulesFromFields(array $editFields, Model $model): array
    {
        $rules = [];

        foreach ($editFields as $editField) {
            if (method_exists($editField, 'validationRules')) {
                $rules = array_merge($rules, $editField->validationRules($model));
            }
        }

        return $rules;
    }

    /**
     * 保存できたことを伝える文言。
     *
     * 何が起きたかは保存した中身で変わる（公開したのか、下書きのままなのか）。
     * モデルが savedMessage() を持っていればそれを使う。
     */
    protected function savedMessage(Model $record, string $default): string
    {
        if (! method_exists($record, 'savedMessage')) {
            return $default;
        }

        $message = $record->savedMessage();

        return is_string($message) && $message !== '' ? $message : $default;
    }
}
