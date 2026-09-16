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
}
