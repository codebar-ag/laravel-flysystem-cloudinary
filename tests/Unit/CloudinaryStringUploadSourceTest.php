<?php

use CodebarAg\FlysystemCloudinary\CloudinaryStringUploadSource;

it('creates a readable temp path and open handle for string contents', function () {
    $contents = 'upload-payload-'.uniqid('', true);

    $source = CloudinaryStringUploadSource::create($contents);

    expect($source)->toBeArray()
        ->and($source['path'])->toBeString()->not->toBeEmpty()
        ->and(is_readable($source['path']))->toBeTrue()
        ->and($source['handle'])->toBeResource();

    expect(file_get_contents($source['path']))->toBe($contents);

    fclose($source['handle']);
});
