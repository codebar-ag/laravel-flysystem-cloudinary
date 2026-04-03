<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryDiskOptions;
use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;
use CodebarAg\FlysystemCloudinary\CloudinaryResourceOperations;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseLogger;
use CodebarAg\FlysystemCloudinary\CloudinaryUrlBuilder;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

it('deliveryUrl returns false when image builder throws NotFound', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('image')->once()->andThrow(new NotFound('nope'));
    });

    $builder = new CloudinaryUrlBuilder(
        $mock,
        new CloudinaryPathNormalizer(null),
        new CloudinaryDiskOptions(null, null, [], true),
        new CloudinaryResourceOperations(new CloudinaryResponseLogger),
    );

    expect($builder->deliveryUrl('any'))->toBeFalse();
});

it('urlViaExplicit prefers secure url when disk option is true', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')
            ->once()
            ->andReturn(new ApiResponse([
                'url' => 'http://insecure',
                'secure_url' => 'https://secure',
            ], []));
    });

    $logger = new CloudinaryResponseLogger;
    $builder = new CloudinaryUrlBuilder(
        $mock,
        new CloudinaryPathNormalizer(null),
        new CloudinaryDiskOptions(null, null, [], true),
        new CloudinaryResourceOperations($logger),
    );

    expect($builder->urlViaExplicit('file'))->toBe('https://secure');
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('urlViaExplicit returns plain url when preferSecureUrl is false', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')
            ->once()
            ->andReturn(new ApiResponse([
                'url' => 'http://insecure',
                'secure_url' => 'https://secure',
            ], []));
    });

    $logger = new CloudinaryResponseLogger;
    $builder = new CloudinaryUrlBuilder(
        $mock,
        new CloudinaryPathNormalizer(null),
        new CloudinaryDiskOptions(null, null, [], false),
        new CloudinaryResourceOperations($logger),
    );

    expect($builder->urlViaExplicit('file'))->toBe('http://insecure');
});
