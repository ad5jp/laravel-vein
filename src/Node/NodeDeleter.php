<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node;

use AD5jp\Vein\Form\Contracts\DeletesRelated;
use AD5jp\Vein\Form\InputManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Node を、ぶら下がっているものごと削除する。
 *
 * 子レコードとファイルを残すと、画面から消えたあとも実体が残り続ける。
 * 掲載を取り下げた内容の画像が URL を直接叩けば見える、という形で効く。
 */
class NodeDeleter
{
    public function delete(Model $record): void
    {
        if ($this->shouldCascade($record)) {
            $this->deleteRelated($record);
        }

        $record->delete();
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

    private function deleteRelated(Model $record): void
    {
        if (! method_exists($record, 'editFields')) {
            return;
        }

        $editFields = (new InputManager)->parseEditField($record->editFields());

        foreach ($editFields as $editField) {
            if ($editField instanceof DeletesRelated) {
                $editField->deleteRelated($record);
            }
        }
    }
}
