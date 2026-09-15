<?php

declare(strict_types=1);

return [
    'admin_uri' => 'admin',
    'admin_guard' => null, // デフォルトガードでない場合、ガード名を指定
    'model_namespaces' => ['\\App\\Models'],
    'upload_disk' => env('FILESYSTEM_DISK', 'local'),
    'upload_path' => 'vein-upload',
    'temporary_disk' => env('FILESYSTEM_DISK', 'local'),
    'temporary_path' => 'vein-tmp',

    // 論理削除（SoftDeletes）のときに、子レコードとファイルも消すか。
    // false なら論理削除では残す（restore で戻せる）。
    // true なら消す（外から見えなくなるが、restore しても戻らない）。
    // 物理削除のときは、この設定にかかわらず必ず消す。
    'cascade_on_soft_delete' => false,
];
