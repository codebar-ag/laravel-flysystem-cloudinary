<?php

namespace CodebarAg\Cloudinary\Events;

use Cloudinary\Api\ApiResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CloudinaryResponseLog
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ApiResponse $response,
    ) {
    }
}
