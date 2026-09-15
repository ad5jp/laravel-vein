<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * 親が削除されるときに、自分が管理している先も片付けるフォーム要素。
 *
 * 独自のフォーム要素がリレーションやストレージを持つ場合はこれを実装する。
 * 実装していない要素は削除時に何もしない（これまでどおり）。
 */
interface DeletesRelated
{
    public function deleteRelated(Model $model): void;
}
