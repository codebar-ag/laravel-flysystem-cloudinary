<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryAdminFolderLocator;
use Mockery\MockInterface;

it('returns true when a paginated folder list contains the path', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->andReturn(new ApiResponse([
                'folders' => [['path' => 'other']],
                'next_cursor' => 'c1',
            ], []));
        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->andReturn(new ApiResponse([
                'folders' => [['path' => 'parent/target']],
                'next_cursor' => null,
            ], []));
    });

    $locator = new CloudinaryAdminFolderLocator;

    expect($locator->folderExists($mock, 'parent/target'))->toBeTrue();
});

it('returns false when folder is not listed', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->andReturn(new ApiResponse(['folders' => [], 'next_cursor' => null], []));
    });

    $locator = new CloudinaryAdminFolderLocator;

    expect($locator->folderExists($mock, 'a/b'))->toBeFalse();
});

it('returns false when subFolders throws', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->andThrow(new Exception('api'));
    });

    $locator = new CloudinaryAdminFolderLocator;

    expect($locator->folderExists($mock, 'a/b'))->toBeFalse();
});

it('uses empty needle for root-level prefixed path', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->subFolders')
            ->once()
            ->andReturn(new ApiResponse([
                'folders' => [['path' => 'only']],
                'next_cursor' => null,
            ], []));
    });

    $locator = new CloudinaryAdminFolderLocator;

    expect($locator->folderExists($mock, 'only'))->toBeTrue();
});
