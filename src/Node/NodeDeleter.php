<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node;

use AD5jp\Vein\Form\Contracts\DeletesRelated;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Form\PendingFileDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Node を、ぶら下がっているものごと削除する。
 *
 * 子レコードとファイルを残すと、画面から消えたあとも実体が残り続ける。
 * 掲載を取り下げた内容の画像が URL を直接叩けば見える、という形で効く。
 *
 * 消す順序は外部キーが決める。ファイルのレコードは複数の行から指されるので、
 * 指している行が全部消えるまで消せない。逆に、子を辿れるのは親が消える前まで
 * （親を消すと ON DELETE CASCADE で子が落ち、リレーションが空になる）。
 *
 *   1. 集める      親より前。ここでしか子のファイルを辿れない
 *   2. 子行を消す  親より前
 *   3. 親を消す
 *   4. ファイルを消す  親より後。誰も指していない状態で消す
 *
 * 全体をトランザクションで包む。途中で外部キーに弾かれたとき、実体だけ消えて
 * レコードが残る状態にしないため（実体の削除は afterCommit に遅らせてある）。
 */
class NodeDeleter
{
    public function delete(Model $record): void
    {
        DB::transaction(function () use ($record): void {
            if (! $this->shouldCascade($record)) {
                $record->delete();

                return;
            }

            // 論理削除では行が残る。残る行が指しているファイルを消すと外部キーに弾かれるので、
            // その行のファイルは集めない（子は消えるので子の分は集める）
            $row_will_be_removed = ! $this->isSoftDeleting($record);

            $files = $this->collectFiles($record, $row_will_be_removed);
            $this->deleteRows($record);

            $record->delete();

            $this->deleteFiles($files);
        });
    }

    /**
     * 物理削除なら必ず片付ける。
     * 論理削除で片付けるかは、戻せなくなることとの兼ね合いなので設定で選ぶ。
     */
    private function shouldCascade(Model $record): bool
    {
        if (! $this->isSoftDeleting($record)) {
            return true;
        }

        return (bool) config('vein.cascade_on_soft_delete', false);
    }

    private function isSoftDeleting(Model $record): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($record), true);
    }

    /**
     * @return list<PendingFileDeletion>
     */
    private function collectFiles(Model $record, bool $row_will_be_removed): array
    {
        $files = [];

        foreach ($this->deletingFields($record) as $editField) {
            $files = array_merge($files, $editField->collectFiles($record, $row_will_be_removed));
        }

        return $files;
    }

    private function deleteRows(Model $record): void
    {
        foreach ($this->deletingFields($record) as $editField) {
            $editField->deleteRows($record);
        }
    }

    /**
     * 集めたファイルのレコードと実体を消す。
     *
     * 実体の削除はコミット後。ここで消すと、後続が失敗したときに
     * レコードはあるのに実体だけ無い状態になる。
     *
     * @param  list<PendingFileDeletion>  $files
     */
    private function deleteFiles(array $files): void
    {
        foreach ($files as $pending) {
            $path = $pending->file->getFilePath();
            $disk = $pending->disk;

            DB::afterCommit(static fn () => Storage::disk($disk)->delete($path));

            $pending->file->delete();
        }
    }

    /**
     * 片付けを受け持つフォーム要素だけを返す。
     *
     * @return list<DeletesRelated>
     */
    private function deletingFields(Model $record): array
    {
        if (! method_exists($record, 'editFields')) {
            return [];
        }

        return $this->collectDeleting((new InputManager)->parseEditField($record->editFields()));
    }

    /**
     * 束ねているだけの要素（Row / Group）の中も辿る。
     *
     * 横に並べたい画像欄は Row の中に置かれる。最上位だけを見ていると、
     * その画像がレコードも実体も残り、どこからも辿れない孤児になる。
     *
     * @param  array<mixed>  $editFields
     * @return list<DeletesRelated>
     */
    private function collectDeleting(array $editFields): array
    {
        $found = [];

        foreach ($editFields as $editField) {
            if ($editField instanceof DeletesRelated) {
                $found[] = $editField;

                // 子レコードの分は Records 自身が受け持つので、その中は辿らない
                continue;
            }

            if (is_object($editField) && property_exists($editField, 'children') && is_array($editField->children)) {
                $found = array_merge($found, $this->collectDeleting($editField->children));
            }
        }

        return $found;
    }
}
