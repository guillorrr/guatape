<?php

use App\Models\User;

return [

    // Filesystem disk for attachment binaries (config/filesystems.php). Empty:
    // the default disk (FILESYSTEM_DISK). Use a private disk: downloads go
    // through the API, which checks permissions.
    'disk' => env('ATTACHMENTS_DISK') ?: null,

    // Max upload size in kilobytes. Keep below PHP's upload_max_filesize (64M
    // in the api image) and nginx's client_max_body_size (64M).
    'max_kb' => 20480,

    // Models that accept attachments, keyed by the URL segment used in
    // /api/v1/attachments/{type}/{id}. A whitelist on purpose: the type comes
    // from the URL and must never name an arbitrary class.
    //   manage: permission to upload and delete
    //   view:   permission to list and download
    'parents' => [
        'users' => ['model' => User::class, 'manage' => 'users.manage', 'view' => 'users.view'],
    ],

];
