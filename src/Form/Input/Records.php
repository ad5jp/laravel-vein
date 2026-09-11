<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\DeletesRelated;
use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Contracts\Record;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Records extends FormControl implements DeletesRelated, Form
{
    public function render(Model $values): string
    {
        // リレーション定義取得
        $relation = $this->verifyRelation($values, $this->key);
        $record_model = $relation->getRelated();

        // フィールド情報取得
        $manager = new InputManager;
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
            $html .= $this->renderRow($record, $editFields, $i);
        }
        $html .= '</div><!--//.__records_list-->';
        $html .= '<div class="mt-3 text-end">';
        $html .= sprintf('<button type="button" class="btn btn-outline-primary __records_add">%sを追加</button>', $this->label ?? '行');
        $html .= '</div>';

        // 「追加」で複製されるテンプレート。JS が [0] を実際の添字に置換する
        $html .= '<script type="application/xml">';
        $html .= $this->renderRow($record_model->newInstance(), $editFields, 0);
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

        $manager = new InputManager;
        $editFields = $manager->parseEditField($record_model->editFields());

        /** @var Collection<Model> */
        $exist_records = $model->{$this->key};
        $request_records = $request[$this->key] ?? [];

        // update or create records
        foreach ($request_records as $request_record) {
            $request_record_key = $request_record[$record_model->getKeyName()] ?? null;
            // HTTP 経由の主キーは文字列、getKey() は整数で返ることが多い
            $record = $this->isFilledKey($request_record_key)
                ? $exist_records->first(fn (Model $row) => (string) $row->getKey() === (string) $request_record_key)
                : null;
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
        $request_records_keys = array_map(
            fn ($key) => (string) $key,
            array_filter(
                array_column($request_records, $record_model->getKeyName()),
                fn ($key) => $this->isFilledKey($key),
            ),
        );
        $exist_records->filter(fn (Model $row) => ! in_array((string) $row->getKey(), $request_records_keys, true))
            ->each(fn (Model $row) => $row->delete());

        return $model;
    }

    /**
     * 親が削除されるとき、子レコードも消す。
     *
     * 子がさらにファイルを持つことがあるので、子の editFields も辿る。
     */
    public function deleteRelated(Model $model): void
    {
        $relation = $this->verifyRelation($model, $this->key);

        $manager = new InputManager;
        $editFields = $manager->parseEditField($relation->getRelated()->editFields());

        foreach ($model->{$this->key} as $record) {
            foreach ($editFields as $editField) {
                if ($editField instanceof DeletesRelated) {
                    $editField->deleteRelated($record);
                }
            }

            $record->delete();
        }
    }

    private function verifyRelation(Model $model, string $key): HasMany
    {
        foreach ([$key, Str::camel($key)] as $relation_method_name) {
            if (method_exists($model, $relation_method_name)) {
                $relation = $model->$relation_method_name();

                if (! $relation instanceof HasMany) {
                    throw new Exception('Model '.get_class($model).' の '.$relation_method_name.'() は HasMany リレーションではありません');
                }

                $file_model = $relation->getRelated();
                if (! $file_model instanceof Record) {
                    throw new Exception('Model '.get_class($file_model).' は File インターフェイスを実装していません');
                }

                return $relation;
            }
        }

        throw new Exception('Model '.get_class($model).' にリレーション '.$key.' が定義されていません');
    }

    /**
     * 1 行分の入力欄を組み立てる。
     *
     * 主キーの hidden も含めて name を書き換えるのが要点。ここが漏れると
     * 主キーが素の名前で送信され、受け側が既存行を見つけられなくなる。
     *
     * @param  Form[]  $editFields
     */
    private function renderRow(Model $record, array $editFields, int $index): string
    {
        $row = sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            e($record->getKeyName()),
            e((string) $record->getKey()),
        );

        foreach ($editFields as $editField) {
            $row .= $editField->render($record);
        }

        return '<div class="list-group-item __records_list_item">'
            .$this->wrapKeys($row, $index)
            .'<button type="button" class="btn btn-sm btn-outline-secondary __records_remove"><i class="bi bi-trash"></i></button>'
            .'</div>';
    }

    /**
     * 行の中の name と data-key を、この Records の添字付きに書き換える。
     *
     * data-key も対象にするのは、FileUpload がそこから name を組み立てるため。
     * 書き換えないと、行の中でアップロードしたファイルが行に紐づかない。
     */
    private function wrapKeys(string $html, int $index): string
    {
        $html = preg_replace_callback(
            '/name="(.*?)"/',
            fn (array $matches) => sprintf('name="%s"', $this->wrapKey($matches[1], $index)),
            $html,
        );

        return preg_replace_callback(
            '/data-key="(.*?)"/',
            fn (array $matches) => sprintf('data-key="%s"', $this->wrapKey($matches[1], $index)),
            $html,
        );
    }

    private function wrapKey(string $key, int $index): string
    {
        // 配列で送る入力（チェックボックス等）は [] を外してから包み、末尾に戻す
        if (str_ends_with($key, '[]')) {
            return sprintf('%s[%s][%s][]', $this->key, $index, substr($key, 0, -2));
        }

        return sprintf('%s[%s][%s]', $this->key, $index, $key);
    }

    /**
     * 主キーが送られてきたか。新しい行では空文字で届く。
     */
    private function isFilledKey(mixed $key): bool
    {
        return $key !== null && $key !== '';
    }
}
