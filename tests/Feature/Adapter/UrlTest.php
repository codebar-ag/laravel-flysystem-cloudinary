<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

it('can get url via request', function () {
    Http::fake();
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
            'url' => '::url::',
            'secure_url' => '::secure-url::',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $url = $adapter->getUrlViaRequest('::path::');

    $this->assertSame('::secure-url::', $url);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 2);
});

it('can get url', function () {
    $cloudinary = new Cloudinary([
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'url' => [
            'secure' => true,
        ],
    ]);

    $adapter = new FlysystemCloudinaryAdapter($cloudinary);

    $url = $adapter->getUrl('::path::');

    expect($url)
        ->toContain('https://', '::path::')
        ->not->toContain('http://');

    $cloudinary = new Cloudinary([
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'url' => [
            'secure' => false,
        ],
    ]);

    $adapter = new FlysystemCloudinaryAdapter($cloudinary);

    $url = $adapter->getUrl('::path::');

    expect($url)->toContain('http://', '::path::')
        ->not->toContain('https://');
});

it('can get url with option', function () {
    $cloudinary = new Cloudinary([
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'url' => [
            'secure' => true,
        ],
    ]);

    $adapter = new FlysystemCloudinaryAdapter($cloudinary);

    $url = $adapter->getUrl([
        'path' => '::path::',
        'options' => [
            'w_64',
            'h_64',
            'c_fill',
            'auto',
        ],
    ]);

    expect($url)
        ->toContain('https://', '::path::')
        ->toContain('w_64', 'h_64', 'c_fill', 'auto')
        ->not->toContain('http://');
});
