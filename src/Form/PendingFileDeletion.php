<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form;

use AD5jp\Vein\Node\Contracts\File;
use Illuminate\Database\Eloquent\Model;

/**
 * 「あとで消すファイル」1 件分。
 *
 * 集める時点と消す時点が親の削除を挟んで離れるため、その間の持ち運び用。
 * どのディスクに置いてあるかはフォーム要素（FileUpload）しか知らないので、
 * レコードと一緒に運ぶ。
 */
final class PendingFileDeletion
{
    public function __construct(
        public readonly Model&File $file,
        public readonly string $disk,
    ) {}
}
