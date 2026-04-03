<?php

use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Storage;

it('registers the cloudinary storage driver', function () {
    config([
        'filesystems.default' => 'cloudinary',
        'filesystems.disks.cloudinary' => [
            'driver' => 'cloudinary',
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'demo'),
            'api_key' => env('CLOUDINARY_API_KEY', '1'),
            'api_secret' => env('CLOUDINARY_API_SECRET', '1'),
        ],
    ]);

    $adapter = Storage::disk('cloudinary')->getAdapter();

    expect($adapter)->toBeInstanceOf(FlysystemCloudinaryAdapter::class);
});
