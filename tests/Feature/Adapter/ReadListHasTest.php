<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

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
    Http::fake(['*' => Http::response('body')]);
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
            'secure_url' => 'https://example.test/file',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $stream = $adapter->readStream('::path::');

    $this->assertIsResource($stream);
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

    $files = iterator_to_array($adapter->listContents('::path::', false));

    $this->assertSame([], $files);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 4);
});
