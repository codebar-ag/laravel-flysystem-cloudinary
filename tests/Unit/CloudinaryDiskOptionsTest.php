<?php

use CodebarAg\FlysystemCloudinary\CloudinaryDiskOptions;
use League\Flysystem\Config;

it('merges upload options in documented order', function () {
    $disk = new CloudinaryDiskOptions('app', 'my_preset', ['async' => true], true);
    $config = new Config(['options' => ['foo' => 'bar']]);

    $opts = $disk->uploadOptionsFor('my-id', $config);

    expect($opts['public_id'])->toBe('my-id')
        ->and($opts['folder'])->toBe('app')
        ->and($opts['upload_preset'])->toBe('my_preset')
        ->and($opts['async'])->toBeTrue()
        ->and($opts['foo'])->toBe('bar');
});

it('omits null folder and preset keys from upload options', function () {
    $disk = new CloudinaryDiskOptions(null, null, [], false);
    $opts = $disk->uploadOptionsFor('id', new Config);

    expect($opts)->not->toHaveKey('folder')
        ->and($opts)->not->toHaveKey('upload_preset');
});

it('prefers disk array over published config for folder and preset', function () {
    config([
        'flysystem-cloudinary.folder' => 'from_published',
        'flysystem-cloudinary.upload_preset' => 'pub_preset',
        'flysystem-cloudinary.secure_url' => false,
        'flysystem-cloudinary.options' => ['x' => 1],
    ]);

    $disk = CloudinaryDiskOptions::fromDiskAndConfig([
        'folder' => 'from_disk',
        'upload_preset' => 'disk_preset',
        'secure_url' => true,
        'options' => ['y' => 2],
    ]);

    expect($disk->folder)->toBe('from_disk')
        ->and($disk->uploadPreset)->toBe('disk_preset')
        ->and($disk->preferSecureUrl)->toBeTrue()
        ->and($disk->globalUploadOptions)->toBe(['y' => 2]);
});

it('falls back to published config when disk omits keys', function () {
    config([
        'flysystem-cloudinary.folder' => 'pub_folder',
        'flysystem-cloudinary.upload_preset' => null,
        'flysystem-cloudinary.secure_url' => false,
        'flysystem-cloudinary.options' => ['merge' => true],
    ]);

    $disk = CloudinaryDiskOptions::fromDiskAndConfig([]);

    expect($disk->folder)->toBe('pub_folder')
        ->and($disk->uploadPreset)->toBeNull()
        ->and($disk->preferSecureUrl)->toBeFalse()
        ->and($disk->globalUploadOptions)->toBe(['merge' => true]);
});
