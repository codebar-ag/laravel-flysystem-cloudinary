<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Support\Facades\Event;
use League\Flysystem\Config;
use League\Flysystem\UnableToDeleteDirectory;
use Mockery\MockInterface;

it('can delete', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->destroy')->once()->andReturn(new ApiResponse([
            'result' => 'ok',
        ], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $adapter->delete('::path::');

    $this->assertTrue($adapter->lastDeleteSucceeded());
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

it('can deleteDirectory after clearing shallow-listed files', function () {
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

    $adapter->deleteDirectory('::path::');

    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 5);
});

it('deleteDirectory throws when deleteFolder fails', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->assets')->times(3)->andReturn(new ApiResponse([
            'resources' => [],
        ], []));
        $mock->shouldReceive('adminApi->subFolders')->once()->andReturn(new ApiResponse([
            'folders' => [],
        ], []));
        $mock->shouldReceive('adminApi->deleteFolder')->once()->andThrow(
            new ApiError('folder not empty')
        );
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    $this->expectException(UnableToDeleteDirectory::class);
    $adapter->deleteDirectory('::path::');
});

it('deleteDir lists a single-prefixed path when folder config is set', function () {
    config(['flysystem-cloudinary.folder' => 'app_uploads']);

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->assets')
            ->times(3)
            ->withArgs(function (array $options) {
                expect($options['prefix'] ?? null)->toBe('app_uploads/::path::');

                return true;
            })
            ->andReturn(new ApiResponse([
                'resources' => [],
            ], []));

        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->withArgs(function (string $folder, array $options = []) {
                expect($folder)->toBe('app_uploads/::path::');

                return true;
            })
            ->andReturn(new ApiResponse([
                'folders' => [],
            ], []));

        $mock->shouldReceive('adminApi->deleteFolder')
            ->once()
            ->with('app_uploads/::path::')
            ->andReturn(new ApiResponse([], []));
    });
    $adapter = new FlysystemCloudinaryAdapter($mock);

    expect($adapter->deleteDir('::path::'))->toBeTrue();
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
