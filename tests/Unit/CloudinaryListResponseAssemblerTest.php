<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryListResponseAssembler;
use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseLogger;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseMapper;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Illuminate\Support\Facades\Event;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Mockery\MockInterface;

it('returns empty array when admin api throws', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->assets')->once()->andThrow(new ApiError('rate'));
    });

    $paths = new CloudinaryPathNormalizer(null);
    $assembler = new CloudinaryListResponseAssembler(
        $mock,
        $paths,
        new CloudinaryResponseMapper($paths),
        new CloudinaryResponseLogger
    );

    expect($assembler->shallowList('prefix'))->toBe([]);
});

it('maps resources and folders to attributes', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->assets')
            ->times(3)
            ->andReturn(new ApiResponse([
                'resources' => [
                    [
                        'public_id' => 'prefix/a',
                        'bytes' => 1,
                        'created_at' => '2021-01-01T00:00:00Z',
                        'etag' => 'e',
                        'access_mode' => 'public',
                    ],
                ],
            ], []));
        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->andReturn(new ApiResponse([
                'folders' => [
                    ['path' => 'prefix/sub', 'name' => 'sub'],
                ],
            ], []));
    });

    $paths = new CloudinaryPathNormalizer(null);
    $assembler = new CloudinaryListResponseAssembler(
        $mock,
        $paths,
        new CloudinaryResponseMapper($paths),
        new CloudinaryResponseLogger
    );

    $items = $assembler->shallowList('prefix');

    expect($items)->toHaveCount(4)
        ->and($items[0])->toBeInstanceOf(FileAttributes::class)
        ->and($items[1])->toBeInstanceOf(FileAttributes::class)
        ->and($items[2])->toBeInstanceOf(FileAttributes::class)
        ->and($items[3])->toBeInstanceOf(DirectoryAttributes::class)
        ->and($items[3]->path())->toBe('prefix/sub');

    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 4);
});
