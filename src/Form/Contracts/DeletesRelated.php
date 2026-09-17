<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use AD5jp\Vein\Form\PendingFileDeletion;
use Illuminate\Database\Eloquent\Model;

/**
 * 親が削除されるときに、自分が管理している先も片付けるフォーム要素。
 *
 * 独自のフォーム要素がリレーションやストレージを持つ場合はこれを実装する。
 * 実装していない要素は削除時に何もしない（これまでどおり）。
 *
 * 片付けは「集める」と「行を消す」に分かれている。外部キーがある環境では、
 * この 3 つを親の削除を挟んで別々の位置に置かなければならないため。
 *
 *   1. collectFiles()  親より前。親を消すと子を辿れなくなるので、先に集める
 *   2. deleteRows()    親より前。ファイルはまだ触らない
 *   3. 親を消す
 *   4. 集めたファイルを消す  親より後。誰も指していない状態にしてから
 *
 * 順序は NodeDeleter が握る。実装側は「集める」「行を消す」だけを受け持つ。
 */
interface DeletesRelated
{
    /**
     * 片付ける対象のファイルを集める。**ここでは消さない。**
     *
     * @param  bool  $row_will_be_removed  この $model の行自体が消えるか。
     *                                     残る行が指しているファイルを消すと外部キーに弾かれるため、
     *                                     消えない行のファイルは集めない
     * @return list<PendingFileDeletion>
     */
    public function collectFiles(Model $model, bool $row_will_be_removed): array;

    /**
     * 親より先に消しておく行を消す。**ファイルには触らない。**
     */
    public function deleteRows(Model $model): void;
}
