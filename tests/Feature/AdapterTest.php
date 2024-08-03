<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use Mockery\MockInterface;

beforeEach(function () {
    Event::fake();

    $cloudinary = new Cloudinary([
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'url' => [
            'secure' => true,
        ],
    ]);

    $this->adapter = new FlysystemCloudinaryAdapter($cloudinary);
});

it('can write', function () {
    $publicId = '::file-path::';
    $contents = '::file-contents::';
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
        $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([
            'public_id' => $publicId,
            'version' => 123456,
            'version_id' => '::version-id::',
            'created_at' => '2021-10-10T10:10:10Z',
            'bytes' => 789,
            'etag' => '::etag::',
            'access_mode' => 'public',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->write($publicId, $contents, new Config);

    $this->assertSame($contents, $adapter->meta['contents']);
    $this->assertSame('::etag::', $adapter->meta['etag']);
    $this->assertSame('text/plain', $adapter->meta['mimetype']);
    $this->assertSame($publicId, $adapter->meta['path']);
    $this->assertSame(789, $adapter->meta['size']);
    $this->assertSame(1633860610, $adapter->meta['timestamp']);
    $this->assertSame('file', $adapter->meta['type']);
    $this->assertSame(123456, $adapter->meta['version']);
    $this->assertSame('::version-id::', $adapter->meta['versionid']);
    $this->assertSame('public', $adapter->meta['visibility']);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can write stream', function () {
    $publicId = '::file-path::';
    $contents = '::file-contents::';
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
        $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([
            'public_id' => $publicId,
            'version' => 123456,
            'version_id' => '::version-id::',
            'created_at' => '2021-10-10T10:10:10Z',
            'bytes' => 789,
            'etag' => '::etag::',
            'access_mode' => 'public',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->writeStream($publicId, $contents, new Config);

    $this->assertSame($contents, $adapter->meta['contents']);
    $this->assertSame('::etag::', $adapter->meta['etag']);
    $this->assertSame('text/plain', $adapter->meta['mimetype']);
    $this->assertSame($publicId, $adapter->meta['path']);
    $this->assertSame(789, $adapter->meta['size']);
    $this->assertSame(1633860610, $adapter->meta['timestamp']);
    $this->assertSame('file', $adapter->meta['type']);
    $this->assertSame(123456, $adapter->meta['version']);
    $this->assertSame('::version-id::', $adapter->meta['versionid']);
    $this->assertSame('public', $adapter->meta['visibility']);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can update', function () {
    $publicId = '::file-path::';
    $contents = '::file-contents::';
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
        $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([
            'public_id' => $publicId,
            'version' => 123456,
            'version_id' => '::version-id::',
            'created_at' => '2021-10-10T10:10:10Z',
            'bytes' => 789,
            'etag' => '::etag::',
            'access_mode' => 'public',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $meta = $adapter->update($publicId, $contents, new Config);

    $this->assertSame($contents, $meta['contents']);
    $this->assertSame('::etag::', $meta['etag']);
    $this->assertSame('text/plain', $meta['mimetype']);
    $this->assertSame($publicId, $meta['path']);
    $this->assertSame(789, $meta['size']);
    $this->assertSame(1633860610, $meta['timestamp']);
    $this->assertSame('file', $meta['type']);
    $this->assertSame(123456, $meta['version']);
    $this->assertSame('::version-id::', $meta['versionid']);
    $this->assertSame('public', $meta['visibility']);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can update stream', function () {
    $publicId = '::file-path::';
    $contents = '::file-contents::';
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
        $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([
            'public_id' => $publicId,
            'version' => 123456,
            'version_id' => '::version-id::',
            'created_at' => '2021-10-10T10:10:10Z',
            'bytes' => 789,
            'etag' => '::etag::',
            'access_mode' => 'public',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $meta = $adapter->updateStream($publicId, $contents, new Config);

    $this->assertSame($contents, $meta['contents']);
    $this->assertSame('::etag::', $meta['etag']);
    $this->assertSame('text/plain', $meta['mimetype']);
    $this->assertSame($publicId, $meta['path']);
    $this->assertSame(789, $meta['size']);
    $this->assertSame(1633860610, $meta['timestamp']);
    $this->assertSame('file', $meta['type']);
    $this->assertSame(123456, $meta['version']);
    $this->assertSame('::version-id::', $meta['versionid']);
    $this->assertSame('public', $meta['visibility']);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can rename', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->rename')->once()->andReturn(new ApiResponse([], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $bool = $adapter->rename('::old-path::', '::new-path::');

    $this->assertTrue($bool);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can copy', function () {
    Http::fake();
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
            'secure_url' => '::url::',
        ], []));
        $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->copy('::from-path::', '::to-path::', new Config);

    $this->assertTrue($adapter->copied);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 2);
});

it('can delete', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->destroy')->once()->andReturn(new ApiResponse([
            'result' => 'ok',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->delete('::path::');

    $this->assertTrue($adapter->deleted);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can delete a directory', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->assets')->times(3)->andReturn(new ApiResponse([
            'resources' => [],
        ], []));
        $mock->shouldReceive('adminApi->subFolders')->once()->andReturn(new ApiResponse([
            'folders' => [],
        ], []));
        $mock->shouldReceive('adminApi->deleteFolder')->once()->andReturn(new ApiResponse([], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $bool = $adapter->deleteDir('::path::');

    $this->assertTrue($bool);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 5);
});

it('can create a directory', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->createFolder')->once()->andReturn(new ApiResponse([], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $meta = $adapter->createDir('::path::', new Config);

    $this->assertSame([
        'path' => '::path::',
        'type' => 'dir',
    ], $meta);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can check if file exists', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $bool = $adapter->has('::path::');

    $this->assertTrue($bool);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can read stream', function () {
    Http::fake();
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
            'secure_url' => '::url::',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $meta = $adapter->readStream('::path::');

    $this->assertIsResource($meta['stream']);
    $this->assertArrayNotHasKey('contents', $meta);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('can list directory contents', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->assets')->times(3)->andReturn(new ApiResponse([
            'resources' => [],
        ], []));

        $mock->shouldReceive('adminApi->subFolders')->once()->andReturn(new ApiResponse([
            'folders' => [],
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $files = $adapter->listContents('::path::');

    $this->assertSame([], $files);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 4);
});

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
    // Secure URL

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

    // Unsecure URL

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
    // Secure URL

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
