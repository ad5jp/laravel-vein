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
];
