<?php

use Cloudinary\Api\ApiResponse;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;

it('holds the api response on the event', function () {
    $response = new ApiResponse(['bytes' => 1], []);
    $event = new FlysystemCloudinaryResponseLog($response);

    expect($event->response)->toBe($response);
});
