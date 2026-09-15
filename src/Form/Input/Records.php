<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Concerns\ResolvesRelations;
use AD5jp\Vein\Form\Contracts\DeletesRelated;
use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Contracts\Record;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Records extends FormControl implements DeletesRelated, Form
{
    use ResolvesRelations;

    /** 行の並び順を入れる列。null なら並べ替えを出さない。 */
    private ?string $sort_column = null;

    /** タイルで並べるか。画像を持つ子レコード向け。 */
    private bool $as_tiles = false;

    /**
     * 行をドラッグで並べ替えられるようにする。
     *
     * 並び順は画面に並んでいる順そのままで、指定した列へ 0 から順に入れる。
     * 番号を手で入れる欄は要らなくなるので、子の editFields からは外す。
     */
    public function sortable(string $column = 'sort_order'): static
    {
        $this->sort_column = $column;

        return $this;
    }

    /**
     * 縦に並べる代わりに、タイルで並べる。
     *
     * 画像を持つ行は高く、縦に積むと 1 つ入れ替えるのに画面 1 枚分を運ぶことになる。
     * タイルを押すと、その行の入力欄がモーダルで開く。
     * タイルの見た目（画像・見出し・印）は、モーダルの中身から画面側で組み立てる。
     */
    public function tiles(): static
    {
        $this->as_tiles = true;

        return $this;
    }

    private function relation(Model $model): HasMany
    {
        /** @var HasMany */
        return $this->resolveRelation($model, $this->key, HasMany::class, Record::class);
    }

    public function render(Model $values): string
    {
        // リレーション定義取得
        $relation = $this->relation($values);
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

        // 見出しの行。畳むボタンは、行が縦に積まれるときだけ出す
        // （タイルはもともと小さく、畳む意味がない）
        $html .= '<div class="__records_head">';
        if ($this->label) {
            $html .= sprintf('<h5>%s</h5>', $this->label);
        }
        if (! $this->as_tiles) {
            $html .= '<div class="__records_view btn-group btn-group-sm" role="group" aria-label="表示の切り替え">'
                .'<button type="button" class="btn btn-outline-secondary active" data-view="detail"'
                .' aria-pressed="true" title="カードで表示"><i class="bi bi-card-text"></i></button>'
                .'<button type="button" class="btn btn-outline-secondary" data-view="compact"'
                .' aria-pressed="false" title="一覧で表示"><i class="bi bi-list-ul"></i></button>'
                .'</div>';
        }
        $html .= '</div>';
        // 欄が 1 つしか無い子レコードは、ラベルと段組みを使わず 1 行に収める。
        // 何の欄かは見出し（h5）で分かるので、行ごとのラベルは重複になる
        $single = ! $this->as_tiles && count($editFields) === 1;

        $html .= sprintf(
            '<div class="%s __records_list%s%s">',
            $this->as_tiles ? '__records_tiles' : 'list-group',
            $this->sort_column === null ? '' : ' __records_sortable',
            $single ? ' is-single' : '',
        );
        foreach ($records as $i => $record) {
            /** @var int $i */
            $html .= $this->renderItem($record, $editFields, $i);
        }
        $html .= '</div><!--//.__records_list-->';
        $html .= '<div class="mt-3 text-end">';
        $html .= sprintf('<button type="button" class="btn btn-outline-primary __records_add">%sを追加</button>', $this->label ?? '行');
        $html .= '</div>';

        // 「追加」で複製されるテンプレート。JS が [0] を実際の添字に置換する
        $html .= '<script type="application/xml">';
        $html .= $this->renderItem($record_model->newInstance(), $editFields, 0);
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

    /**
     * 子レコードの規則は key.*.子のkey の形にする。
     */
    public function validationRules(Model $model): array
    {
        $rules = parent::validationRules($model);

        $record_model = $this->relation($model)->getRelated();
        $editFields = (new InputManager)->parseEditField($record_model->editFields());

        foreach ($editFields as $editField) {
            if (! $editField instanceof FormControl) {
                continue;
            }

            foreach ($editField->validationRules($record_model) as $child_key => $child_rules) {
                $rules[sprintf('%s.*.%s', $this->key, $child_key)] = $child_rules;
            }
        }

        return $rules;
    }

    protected function applyBeforeSave(Model $model, array $request): Model
    {
        // DO NOTHING
        return $model;
    }

    protected function applyAfterSave(Model $model, array $request): Model
    {
        // リレーション定義取得
        $relation = $this->relation($model);
        $record_model = $relation->getRelated();
        $foreign_key = $relation->getForeignKeyName();

        $manager = new InputManager;
        $editFields = $manager->parseEditField($record_model->editFields());

        /** @var Collection<Model> */
        $exist_records = $model->{$this->key};
        $request_records = $request[$this->key] ?? [];

        // update or create records
        // 並び順は届いた順（= 画面に並んでいた順）で振り直す
        $position = 0;

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

            // 子の editFields より後に入れる。手入力の値があっても画面の順を正とする
            if ($this->sort_column !== null) {
                $record->{$this->sort_column} = $position;
            }

            $record->save();
            $position++;
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
        $relation = $this->relation($model);

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

    /**
     * 1 行分の入力欄を組み立てる。
     *
     * 主キーの hidden も含めて name を書き換えるのが要点。ここが漏れると
     * 主キーが素の名前で送信され、受け側が既存行を見つけられなくなる。
     *
     * @param  Form[]  $editFields
     */
    private function renderItem(Model $record, array $editFields, int $index): string
    {
        return $this->as_tiles
            ? $this->renderTile($record, $editFields, $index)
            : $this->renderRow($record, $editFields, $index);
    }

    /**
     * 1 行分の入力欄そのもの。行にもタイルのモーダルにも、これを入れる。
     *
     * @param  Form[]  $editFields
     */
    private function renderFields(Model $record, array $editFields, int $index): string
    {
        $row = sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            e($record->getKeyName()),
            e((string) $record->getKey()),
        );

        foreach ($editFields as $editField) {
            // 検証のキーは images.0.caption の形になる。どの行が弾かれたのかを
            // 行の中で示せるよう、親のキーと添字を渡しておく
            if ($editField instanceof FormControl) {
                $editField->withErrorKeyPrefix(sprintf('%s.%d', $this->key, $index));
            }

            $row .= $editField->render($record);
        }

        return $this->wrapKeys($row, $index);
    }

    private function renderRow(Model $record, array $editFields, int $index): string
    {
        $handle = $this->sort_column === null
            ? ''
            : '<span class="__records_handle" title="ドラッグして並べ替え" aria-hidden="true">'
                .'<i class="bi bi-grip-vertical"></i></span>';

        return sprintf('<div class="list-group-item __records_list_item%s">', $this->sort_column === null ? '' : ' is-sortable')
            .$handle
            .$this->renderFields($record, $editFields, $index)
            .'<button type="button" class="btn btn-sm btn-outline-secondary __records_remove"><i class="bi bi-trash"></i></button>'
            .'</div>';
    }

    /**
     * タイル 1 枚と、その中身を入れたモーダル。
     *
     * タイルの表側は空で出す。画像も見出しも、モーダルの中身から画面側で写す。
     * 同じ画像を 2 回埋め込まずに済み、アップロードし直したときも自動でついてくる。
     */
    private function renderTile(Model $record, array $editFields, int $index): string
    {
        $modalId = sprintf(
            '__rec_%s_%d',
            preg_replace('/[^A-Za-z0-9_]/', '_', $this->key),
            $index,
        );
        $title = $this->label ?? '行';

        return sprintf(
            '<div class="__records_tile">'
            .'<button type="button" class="__records_tile_face" data-bs-toggle="modal" data-bs-target="#%s">'
            .'<span class="__records_tile_image"></span>'
            .'<span class="__records_tile_label"></span>'
            .'<span class="__records_tile_badge"></span>'
            .'</button>'
            .'<div class="modal fade __records_tile_modal" id="%s" tabindex="-1" aria-hidden="true">'
            .'<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">'
            .'<div class="modal-header"><h5 class="modal-title">%s</h5>'
            .'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button></div>'
            .'<div class="modal-body">%s</div>'
            .'<div class="modal-footer justify-content-between">'
            .'<button type="button" class="btn btn-sm btn-outline-danger __records_remove">この%sを削除</button>'
            .'<button type="button" class="btn btn-primary" data-bs-dismiss="modal">閉じる</button>'
            .'</div></div></div></div>'
            .'</div>',
            e($modalId),
            e($modalId),
            e($title),
            $this->renderFields($record, $editFields, $index),
            e($title),
        );
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
