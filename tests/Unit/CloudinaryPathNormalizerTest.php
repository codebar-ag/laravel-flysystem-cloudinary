<?php

use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;

it('returns trimmed path when folder is null', function () {
    $normalizer = new CloudinaryPathNormalizer(null);

    expect($normalizer->prefixed('  foo/bar  '))->toBe('foo/bar');
    expect($normalizer->logical('foo/bar'))->toBe('foo/bar');
});

it('returns trimmed path when folder is empty string', function () {
    $normalizer = new CloudinaryPathNormalizer('');

    expect($normalizer->prefixed('foo'))->toBe('foo');
});

it('prefixes logical path with folder', function () {
    $normalizer = new CloudinaryPathNormalizer('app_uploads');

    expect($normalizer->prefixed('file.jpg'))->toBe('app_uploads/file.jpg');
    expect($normalizer->prefixed('/nested/path'))->toBe('app_uploads/nested/path');
    expect($normalizer->prefixed(''))->toBe('app_uploads');
});

it('strips folder prefix for logical path', function () {
    $normalizer = new CloudinaryPathNormalizer('app_uploads');

    expect($normalizer->logical('app_uploads/file.jpg'))->toBe('file.jpg');
    expect($normalizer->logical('app_uploads/nested/a'))->toBe('nested/a');
});

it('trims folder slashes when prefixing', function () {
    $normalizer = new CloudinaryPathNormalizer(' /my/folder/ ');

    expect($normalizer->prefixed('x'))->toBe('my/folder/x');
    expect($normalizer->prefixed(''))->toBe('my/folder');
});
