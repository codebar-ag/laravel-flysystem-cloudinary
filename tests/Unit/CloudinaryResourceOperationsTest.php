<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryResourceOperations;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseLogger;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

it('explicit returns on first successful resource type and logs once', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')
            ->once()
            ->andReturn(new ApiResponse(['public_id' => 'x'], []));
    });

    $ops = new CloudinaryResourceOperations(new CloudinaryResponseLogger);
    $result = $ops->explicit($mock, 'prefixed/id');

    expect($result)->toBeInstanceOf(ApiResponse::class);
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('explicit tries resource types until not found then throws last NotFound', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->explicit')
            ->times(3)
            ->andThrow(new NotFound('missing'));
    });

    $ops = new CloudinaryResourceOperations(new CloudinaryResponseLogger);

    $this->expectException(NotFound::class);
    $ops->explicit($mock, 'missing/asset');
});

it('destroy returns true when first destroy returns ok', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->destroy')
            ->once()
            ->andReturn(new ApiResponse(['result' => 'ok'], []));
    });

    $ops = new CloudinaryResourceOperations(new CloudinaryResponseLogger);

    expect($ops->destroy($mock, 'path'))->toBeTrue();
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
});

it('destroy tries types until ok', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->destroy')
            ->twice()
            ->andReturn(
                new ApiResponse(['result' => 'noop'], []),
                new ApiResponse(['result' => 'ok'], []),
            );
    });

    $ops = new CloudinaryResourceOperations(new CloudinaryResponseLogger);

    expect($ops->destroy($mock, 'path'))->toBeTrue();
    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 2);
});

it('destroy returns false when ApiError is thrown', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->destroy')
            ->once()
            ->andThrow(new ApiError('fail'));
    });

    $ops = new CloudinaryResourceOperations(new CloudinaryResponseLogger);

    expect($ops->destroy($mock, 'path'))->toBeFalse();
});

it('destroy returns false when no type returns ok', function () {
    Event::fake();

    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('uploadApi->destroy')
            ->times(3)
            ->andReturn(new ApiResponse(['result' => 'not_found'], []));
    });

    $ops = new CloudinaryResourceOperations(new CloudinaryResponseLogger);

    expect($ops->destroy($mock, 'path'))->toBeFalse();
});
