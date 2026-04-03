<?php

require_once __DIR__.'/cloudinary_integration.php';

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\Tests\TestCase;
use Illuminate\Support\Facades\Event;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    Event::fake();

    config(['flysystem-cloudinary.folder' => env('CLOUDINARY_FOLDER')]);

    $this->cloudinary = new Cloudinary([
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'url' => [
            'secure' => true,
        ],
    ]);

    $this->adapter = new FlysystemCloudinaryAdapter($this->cloudinary);
})->in(__DIR__.'/Feature/Adapter');

uses()->group('integration')->in(__DIR__.'/Integration');
