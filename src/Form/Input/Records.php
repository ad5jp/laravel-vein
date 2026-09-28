<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Concerns\ResolvesRelations;
use AD5jp\Vein\Form\Contracts\DeletesRelated;
use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\Contracts\ScopesErrorKeys;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Contracts\Record;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        // 子レコードの検証規則は親に集まっている。自分たちの分だけ切り出す
        $this->child_rules = [];
        $prefix = $this->key.'.*.';

        if (method_exists($values, 'editValidatorRules')) {
            foreach ($values->editValidatorRules() as $key => $rule) {
                if (str_starts_with($key, $prefix)) {
                    $this->child_rules[substr($key, strlen($prefix))] = $rule;
                }
            }
        }

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

        $html .= sprintf(
            '<div class="__records mt-5 mb-5" data-records="%s" data-nextkey="%s">',
            e($this->key),
            $records->count(),
        );

        // 欄が 1 つしか無い子レコードは、ラベルと段組みを使わず 1 行に収める。
        // 何の欄かは見出し（h5）で分かるので、行ごとのラベルは重複になる
        $single = ! $this->as_tiles && count($editFields) === 1;

        // 見出しの行。表示の切り替えは、行が縦に積まれて高くなるときだけ出す
        // （タイルと 1 行の子レコードは、もともと低いので切り替える先がない）
        $html .= '<div class="__records_head">';
        if ($this->label) {
            // 見出しに必須の印は出さない。子レコードは 0 件でも保存できるため、
            // 「1 行以上要る」と読めてしまう。必須なのは行の中の欄で、そちらに出る
            $html .= sprintf('<h5>%s</h5>', e($this->label));
        }
        if (! $this->as_tiles && ! $single) {
            $html .= '<div class="__records_view btn-group btn-group-sm" role="group" aria-label="表示の切り替え">'
                .'<button type="button" class="btn btn-outline-secondary active" data-view="detail"'
                .' aria-pressed="true" title="カードで表示"><i class="bi bi-card-text"></i></button>'
                .'<button type="button" class="btn btn-outline-secondary" data-view="compact"'
                .' aria-pressed="false" title="一覧で表示"><i class="bi bi-list-ul"></i></button>'
                .'</div>';
        }
        $html .= '</div>';

        if ($this->hint !== null) {
            $html .= sprintf('<p class="__records_hint">%s</p>', e($this->hint));
        }

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
        $html .= $this->renderItem($record_model->newInstance(), $editFields, 0, true);
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

            // 子の入力欄にも保存後の処理がある（FileUpload は旧ファイルをここで消す）。
            // 親の外部キーが新しいファイルに向いたあとでなければ消せない
            foreach ($editFields as $editField) {
                $record = $editField->afterSave($record, $request_record);
            }

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
        // 画面から外された行。持っていたファイルも一緒に片付ける。
        // 行だけ消すとファイルが誰からも指されないまま残る
        $removed = $exist_records
            ->filter(fn (Model $row) => ! in_array((string) $row->getKey(), $request_records_keys, true));

        $deleting_fields = array_values(array_filter(
            $editFields,
            static fn ($editField) => $editField instanceof DeletesRelated,
        ));

        $files = [];

        foreach ($removed as $row) {
            foreach ($deleting_fields as $editField) {
                $files = array_merge($files, $editField->collectFiles($row, true));
            }
        }

        $removed->each(fn (Model $row) => $row->delete());

        foreach ($files as $pending) {
            $path = $pending->file->getFilePath();
            $disk = $pending->disk;

            DB::afterCommit(static fn () => Storage::disk($disk)->delete($path));

            $pending->file->delete();
        }

        return $model;
    }

    /**
     * 親が削除されるとき、子が持っているファイルを「あとで消す対象」として返す。
     *
     * **親を消したあとでは辿れない。** 外部キーの ON DELETE CASCADE で子行が
     * 落ちてしまい、リレーションが空を返すため、集めるのは必ず親より前。
     */
    public function collectFiles(Model $model, bool $row_will_be_removed): array
    {
        $files = [];

        // 子行は必ず消えるので、親が残る場合でも子のファイルは集める
        foreach ($model->{$this->key} as $record) {
            foreach ($this->childDeletingFields($model) as $editField) {
                $files = array_merge($files, $editField->collectFiles($record, true));
            }
        }

        return $files;
    }

    public function deleteRows(Model $model): void
    {
        foreach ($model->{$this->key} as $record) {
            foreach ($this->childDeletingFields($model) as $editField) {
                $editField->deleteRows($record);
            }

            $record->delete();
        }
    }

    /**
     * 子の入力欄のうち、片付けを受け持つものだけ。
     *
     * @return list<DeletesRelated>
     */
    private function childDeletingFields(Model $model): array
    {
        $relation = $this->relation($model);

        $editFields = (new InputManager)->parseEditField($relation->getRelated()->editFields());

        return array_values(array_filter(
            $editFields,
            static fn ($editField) => $editField instanceof DeletesRelated,
        ));
    }

    /**
     * 1 行分の入力欄を組み立てる。
     *
     * 主キーの hidden も含めて name を書き換えるのが要点。ここが漏れると
     * 主キーが素の名前で送信され、受け側が既存行を見つけられなくなる。
     *
     * @param  Form[]  $editFields
     */
    private function renderItem(Model $record, array $editFields, int $index, bool $as_template = false): string
    {
        return $this->as_tiles
            ? $this->renderTile($record, $editFields, $index, $as_template)
            : $this->renderRow($record, $editFields, $index, $as_template);
    }

    /**
     * 1 行分の入力欄そのもの。行にもタイルのモーダルにも、これを入れる。
     *
     * @param  Form[]  $editFields
     */
    private function renderFields(Model $record, array $editFields, int $index, bool $as_template = false): string
    {
        $row = sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            e($record->getKeyName()),
            e((string) $record->getKey()),
        );

        foreach ($editFields as $editField) {
            // 必須の印は検証の規則を正とする。子レコードの規則は親に
            // images.*.caption の形で集まっているため、子は自分では引けない
            if ($editField instanceof FormControl) {
                $editField->withScopedRules($this->child_rules);
            } elseif ($editField instanceof Row) {
                foreach ($editField->children as $child) {
                    if ($child instanceof FormControl) {
                        $child->withScopedRules($this->child_rules);
                    }
                }
            }

            // 検証のキーは images.0.caption の形になる。どの行が弾かれたのかを
            // 行の中で示せるよう、親のキーと添字を渡しておく。Row / Group で
            // 束ねた欄にも届くよう、受け口は ScopesErrorKeys で判定する。
            // 「追加」の雛形は添字 0 で描くが、まだ入力されていない行なので、
            // 0 行目のエラーを引き継がないよう、どれにも当たらないキーを渡す
            if ($editField instanceof ScopesErrorKeys) {
                $editField->withErrorKeyPrefix(sprintf(
                    '%s.%s',
                    $this->key,
                    $as_template ? '__template' : $index,
                ));
            }

            $row .= $editField->render($record);
        }

        return $this->wrapKeys($row, $index);
    }

    /**
     * 子の欄に配る検証規則。
     *
     * 親の images.*.caption を caption に読み替えたもの。行を描くときに子へ渡す。
     *
     * @var array<string, mixed>
     */
    private array $child_rules = [];

    private function renderRow(Model $record, array $editFields, int $index, bool $as_template = false): string
    {
        $handle = $this->sort_column === null
            ? ''
            : '<span class="__records_handle" title="ドラッグして並べ替え" aria-hidden="true">'
                .'<i class="bi bi-grip-vertical"></i></span>';

        return sprintf('<div class="list-group-item __records_list_item%s">', $this->sort_column === null ? '' : ' is-sortable')
            .$handle
            .$this->renderFields($record, $editFields, $index, $as_template)
            .sprintf(
                '<button type="button" class="btn btn-sm btn-outline-secondary __records_remove"'
                .' aria-label="%s"><i class="bi bi-trash" aria-hidden="true"></i></button>',
                // 何行目かは入れない。「追加」で増えた行は雛形の複製で、
                // 番号が振り直されないため嘘になる
                e(sprintf('この%sを削除', $this->label ?? '行')),
            )
            .'</div>';
    }

    /**
     * タイル 1 枚と、その中身を入れたモーダル。
     *
     * タイルの表側は空で出す。画像も見出しも、モーダルの中身から画面側で写す。
     * 同じ画像を 2 回埋め込まずに済み、アップロードし直したときも自動でついてくる。
     */
    private function renderTile(Model $record, array $editFields, int $index, bool $as_template = false): string
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
            $this->renderFields($record, $editFields, $index, $as_template),
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

        $html = preg_replace_callback(
            '/data-key="(.*?)"/',
            fn (array $matches) => sprintf('data-key="%s"', $this->wrapKey($matches[1], $index)),
            $html,
        );

        // ラベルと入力を結ぶ id も、名前と同じ規則で行ごとに分ける。
        // 分けないと、どの行のラベルを押しても 1 行目の欄に入ってしまう
        return preg_replace_callback(
            '/\b(id|for|aria-labelledby)="__([fl])_(.*?)"/',
            fn (array $matches) => sprintf(
                '%s="__%s_%s"',
                $matches[1],
                $matches[2],
                $this->wrapKey($matches[3], $index),
            ),
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
