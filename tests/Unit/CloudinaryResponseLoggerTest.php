<?php

use Cloudinary\Api\ApiResponse;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseLogger;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Illuminate\Support\Facades\Event;

it('dispatches FlysystemCloudinaryResponseLog with the api response', function () {
    Event::fake();

    $response = new ApiResponse(['result' => 'ok'], []);
    (new CloudinaryResponseLogger)->log($response);

    Event::assertDispatched(FlysystemCloudinaryResponseLog::class, function (FlysystemCloudinaryResponseLog $event) use ($response) {
        return $event->response === $response;
    });
});
