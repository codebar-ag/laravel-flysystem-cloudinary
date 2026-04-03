<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\UnableToCopyFile;
use Mockery\MockInterface;

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
        $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([
            'public_id' => '::to-path::',
            'version' => 1,
            'version_id' => 'v1',
            'created_at' => '2021-10-10T10:10:10Z',
            'bytes' => 3,
            'etag' => 'e',
            'access_mode' => 'public',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->copy('::from-path::', '::to-path::', new Config);

    $this->assertTrue($adapter->lastCopySucceeded());
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 2);
});

it('copy uses logical destination public_id when folder config is set', function () {
    Http::fake();
    config(['flysystem-cloudinary.folder' => 'app_uploads']);

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')
            ->once()
            ->withArgs(function (string $publicId, array $options) {
                expect($publicId)->toBe('app_uploads/::from-path::');

                return true;
            })
            ->andReturn(new ApiResponse([
                'secure_url' => 'https://example.test/file',
            ], []));

        $mock->shouldReceive('uploadApi->upload')
            ->once()
            ->withArgs(function (mixed $file, array $options) {
                expect($file)->toBeString()->not->toBeEmpty();
                expect($options['public_id'] ?? null)->toBe('::to-path::');
                expect($options['folder'] ?? null)->toBe('app_uploads');

                return true;
            })
            ->andReturn(new ApiResponse([
                'public_id' => 'app_uploads/::to-path::',
                'version' => 1,
                'version_id' => 'v1',
                'created_at' => '2021-10-10T10:10:10Z',
                'bytes' => 3,
                'etag' => 'e',
                'access_mode' => 'public',
            ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->copy('::from-path::', '::to-path::', new Config);

    expect($adapter->lastCopySucceeded())->toBeTrue();
});

it('does not copy if upload fails after read', function () {
    Http::fake();
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
            'secure_url' => 'https://example.test/file',
        ], []));
        $mock->shouldReceive('uploadApi->upload')->once()->andThrow(new ApiError('upload failed'));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $this->expectException(UnableToCopyFile::class);
    $adapter->copy('::from-path::', '::to-path::', new Config);
});

it('does not copy if file is not found', function () {
    Http::fake();
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')->times(3)->andThrow(new NotFound('not found'));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $this->expectException(UnableToCopyFile::class);
    $adapter->copy('::missing::', '::to-path::', new Config);
});
