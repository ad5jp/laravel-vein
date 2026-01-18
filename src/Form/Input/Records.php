<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Contracts\Record;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Records extends FormControl implements Form
{
    public function render(Model $values): string
    {
        // リレーション定義取得
        $relation = $this->verifyRelation($values, $this->key);
        $record_model = $relation->getRelated();

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($record_model->editFields());

        // リレーションデータ取得
        $records = $values->{$this->key};
        // old の対応
        // TODO Record のプロパティに親と同じプロパティ名があれば、親の old に上書きされる不具合がある
        if ($old = old($this->key)) {
            $records = collect();
            foreach ($old as $row) {
                $old_row = $record_model->newInstance();
                foreach ($row as $key => $value) {
                    $old_row->$key = $value;
                }
                $records->push($old_row);
            }
        }

        // 入力欄構築
        $html = '';

        $html .= sprintf('<div class="__records mt-5 mb-5" data-nextkey="%s">', $records->count());
        if ($this->label) {
            $html .= sprintf('<h5>%s</h5>', $this->label);
        }
        $html .= '<div class="list-group __records_list">';
        foreach ($records as $i => $record) {
            /** @var int $i */
            $html .= '<div class="list-group-item __records_list_item">';
            $html .= sprintf('<input type="hidden" name="%s" value="%s" />', $record->getKeyName(), $record->getKey());
            foreach ($editFields as $editField) {
                $input = $editField->render($record);
                $input = preg_replace_callback('/name="(.*?)"/', fn ($matches) => $this->wrapName($matches, $i), $input); // name を置換
                $html .= $input;
            }
            $html .= '<button type="button" class="btn btn-sm btn-outline-secondary __records_remove"><i class="bi bi-trash"></i></button>';
            $html .= '</div>';
        }
        $html .= '</div><!--//.__records_list-->';
        $html .= '<div class="mt-3 text-end">';
        $html .= sprintf('<button type="button" class="btn btn-outline-primary __records_add">%sを追加</button>', $this->label ?? '行');
        $html .= '</div>';

        $record = $record_model->newInstance();
        $html .= '<script type="application/xml">';
        $html .= '<div class="list-group-item __records_list_item">';
        $html .= sprintf('<input type="hidden" name="%s" value="" />', $record->getKeyName());
        foreach ($editFields as $editField) {
            $input = $editField->render($record);
            $input = preg_replace_callback('/name="(.*?)"/', fn ($matches) => $this->wrapName($matches, 0), $input); // name を置換
            $html .= $input;
        }
        $html .= '<button type="button" class="btn btn-sm btn-outline-secondary __records_remove"><i class="bi bi-trash"></i></button>';
        $html .= '</div>';
        $html .= '</script>';
        $html .= '</div><!--//.__records-->';

        return $html;
    }

    public function renderColumn(?Model $values = null): string
    {
        throw new Exception('Records cannot be rendered as Column');
    }

    public function renderInline(?Model $values = null): string
    {
        throw new Exception('Records cannot be rendered inline');
    }

    public function beforeSave(Model $model, Arrayable|array $request): Model
    {
        // DO NOTHING
        return $model;
    }

    public function afterSave(Model $model, Arrayable|array $request): Model
    {
        if ($request instanceof Arrayable) {
            $request = $request->toArray();
        }

        // リレーション定義取得
        $relation = $this->verifyRelation($model, $this->key);
        $record_model = $relation->getRelated();
        $foreign_key = $relation->getForeignKeyName();

        $manager = new InputManager();
        $editFields = $manager->parseEditField($record_model->editFields());

        /** @var Collection<Model> */
        $exist_records = $model->{$this->key};
        $request_records = $request[$this->key] ?? [];

        // update or create records
        foreach ($request_records as $request_record) {
            $request_record_key = $request_record[$record_model->getKeyName()] ?? null;
            $record = $request_record_key ? $exist_records->first(fn (Model $row) => $row->getKey() === $request_record_key) : null;
            if ($record === null) {
                $record = $record_model->newInstance();
                $record->$foreign_key = $model->getKey();
            }
            foreach ($editFields as $editField) {
                $record = $editField->beforeSave($record, $request_record);
            }
            $record->save();
        }

        // delete missing records
        $request_records_keys = array_column($request_records, $record_model->getKeyName());
        $exist_records->filter(fn (Model $row) => !in_array($row->getKey(), $request_records_keys))
            ->each(fn (Model $row) => $row->delete());

        return $model;
    }

    private function verifyRelation(Model $model, string $key): HasMany
    {
        foreach ([$key, Str::camel($key)] as $relation_method_name) {
            if (method_exists($model, $relation_method_name)) {
                $relation = $model->$relation_method_name();

                if (!$relation instanceof HasMany) {
                    throw new Exception('Model ' . get_class($model) . ' の ' . $relation_method_name . '() は HasMany リレーションではありません');
                }

                $file_model = $relation->getRelated();
                if (!$file_model instanceof Record) {
                    throw new Exception('Model ' . get_class($file_model) . ' は File インターフェイスを実装していません');
                }

                return $relation;
            }
        }

        throw new Exception('Model ' . get_class($model) . ' にリレーション ' . $key . ' が定義されていません');
    }

    private function wrapName(array $matches, int $index): string
    {
        if (str_ends_with($matches[1], '[]')) {
            return sprintf('name="%s[%s][%s][]"', $this->key, $index, $matches[1]);
        }

        return sprintf('name="%s[%s][%s]"', $this->key, $index, $matches[1]);
    }
}
