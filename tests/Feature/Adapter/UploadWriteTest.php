<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Event;
use League\Flysystem\Config;
use Mockery\MockInterface;

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

    $meta = $adapter->lastUploadMetadata();
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

    $meta = $adapter->lastUploadMetadata();
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

it('update does not set lastUploadMetadata', function () {
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

    expect($adapter->lastUploadMetadata())->toBeFalse();
    $adapter->update($publicId, $contents, new Config);
    expect($adapter->lastUploadMetadata())->toBeFalse();
});

it('update leaves lastUploadMetadata from a previous write unchanged', function () {
    $publicId = '::file-path::';
    $first = 'first-bytes';
    $second = 'second-bytes';
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
        $mock->shouldReceive('uploadApi->upload')
            ->twice()
            ->andReturn(new ApiResponse([
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

    $adapter->write($publicId, $first, new Config);
    $afterWrite = $adapter->lastUploadMetadata();
    $returned = $adapter->update($publicId, $second, new Config);

    expect($returned['contents'])->toBe($second);
    expect($adapter->lastUploadMetadata())->toBe($afterWrite);
    expect($adapter->lastUploadMetadata()['contents'])->toBe($first);
});

it('update returns false when upload fails', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->upload')->once()->andThrow(new ApiError('failed'));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    expect($adapter->update('::path::', 'x', new Config))->toBeFalse();
    expect($adapter->lastUploadMetadata())->toBeFalse();
});

it('updateStream returns false when upload fails', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->upload')->once()->andThrow(new ApiError('failed'));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    expect($adapter->updateStream('::path::', 'x', new Config))->toBeFalse();
});

it('write passes a local file path string to upload for string contents', function () {
    $publicId = '::file-path::';
    $contents = '::file-contents::';
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
        $mock->shouldReceive('uploadApi->upload')
            ->once()
            ->withArgs(function (mixed $file, array $options) use ($publicId) {
                expect($file)->toBeString();
                expect(is_readable($file))->toBeTrue();

                return ($options['public_id'] ?? null) === $publicId;
            })
            ->andReturn(new ApiResponse([
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

    expect($adapter->lastUploadMetadata()['path'])->toBe($publicId);
});
