<?php

namespace CodebarAg\FlysystemCloudinary\Events;

use Cloudinary\Api\ApiResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlysystemCloudinaryResponseLog
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ApiResponse $response,
    ) {}
}
