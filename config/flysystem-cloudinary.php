<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Upload Preset
    |--------------------------------------------------------------------------
    |
    | Upload preset allow you to define the default behavior for all your
    | assets. They have precedence over client-side upload parameters.
    | You can define your upload preset in your cloudinary settings.
    |
    */

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Folder
    |--------------------------------------------------------------------------
    |
    | An optional folder name where the uploaded asset will be stored. The
    | public ID contains the full path of the uploaded asset, including
    | the folder name. This is very useful to prefix assets directly.
    |
    */

    'folder' => env('CLOUDINARY_FOLDER'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Secure URL
    |--------------------------------------------------------------------------
    |
    | This value determines that the asset delivery is forced to use HTTPS
    | URLs. If disabled all your assets will be delivered as HTTP URLs.
    | Please do not use unsecure URLs in your production application.
    |
    */

    'secure_url' => (bool) env('CLOUDINARY_SECURE_URL', true),

];
