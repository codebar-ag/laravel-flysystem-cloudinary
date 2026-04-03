<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;

final class CloudinaryResponseLogger
{
    public function log(ApiResponse $response): void
    {
        event(new FlysystemCloudinaryResponseLog($response));
    }
}
