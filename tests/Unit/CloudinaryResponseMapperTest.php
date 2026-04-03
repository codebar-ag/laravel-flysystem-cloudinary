<?php

use Cloudinary\Api\ApiResponse;
use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseMapper;

it('normalizeUploadOrExplicit strips folder prefix for logical path', function () {
    $mapper = new CloudinaryResponseMapper(new CloudinaryPathNormalizer('app'));
    $row = new ApiResponse([
        'etag' => 'e',
        'bytes' => 10,
        'created_at' => '2021-01-01T00:00:00Z',
        'version' => 1,
        'version_id' => 'v1',
        'access_mode' => 'public',
    ], []);

    $normalized = $mapper->normalizeUploadOrExplicit($row, 'app/file.txt', 'body');

    expect($normalized['path'])->toBe('file.txt')
        ->and($normalized['contents'])->toBe('body')
        ->and($normalized['etag'])->toBe('e')
        ->and($normalized['size'])->toBe(10)
        ->and($normalized['visibility'])->toBe('public')
        ->and($normalized['version'])->toBe(1)
        ->and($normalized['versionid'])->toBe('v1');
});

it('normalizeUploadOrExplicit marks private access_mode', function () {
    $mapper = new CloudinaryResponseMapper(new CloudinaryPathNormalizer(null));
    $row = new ApiResponse(['access_mode' => 'authenticated'], []);

    expect($mapper->normalizeUploadOrExplicit($row, 'a', null)['visibility'])->toBe('private');
});

it('normalizedToFileAttributes maps extra keys and null timestamp when strtotime false', function () {
    $mapper = new CloudinaryResponseMapper(new CloudinaryPathNormalizer(null));
    $normalized = [
        'path' => 'p',
        'size' => '5',
        'visibility' => 'public',
        'timestamp' => false,
        'mimetype' => 'text/plain',
        'etag' => '',
        'version' => null,
    ];

    $fa = $mapper->normalizedToFileAttributes($normalized);

    expect($fa->path())->toBe('p')
        ->and($fa->fileSize())->toBe(5)
        ->and($fa->lastModified())->toBeNull();
});

it('adminAssetToFileAttributes builds mime type string and logical path', function () {
    $mapper = new CloudinaryResponseMapper(new CloudinaryPathNormalizer('root'));
    $fa = $mapper->adminAssetToFileAttributes([
        'public_id' => 'root/sub/file',
        'bytes' => 100,
        'created_at' => '2020-06-15T12:00:00Z',
        'resource_type' => 'image',
        'format' => 'jpg',
    ]);

    expect($fa->path())->toBe('sub/file')
        ->and($fa->mimeType())->toBe('image/jpg')
        ->and($fa->fileSize())->toBe(100);
});

it('extractExtraMetadata skips empty strings', function () {
    $mapper = new CloudinaryResponseMapper(new CloudinaryPathNormalizer(null));

    $extra = $mapper->extractExtraMetadata([
        'version' => 2,
        'url' => '',
        'secure_url' => 'https://x',
    ]);

    expect($extra)->toHaveKey('version')
        ->and($extra)->toHaveKey('secure_url')
        ->and($extra)->not->toHaveKey('url');
});
