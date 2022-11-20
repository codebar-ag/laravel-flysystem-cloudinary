<?php

namespace CodebarAg\FlysystemCloudinary\Tests\Integration;

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\Tests\TestCase;

/** @group Integration */
class CloudinaryTest extends TestCase
{
    protected FlysystemCloudinaryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $cloudinary = new Cloudinary([
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->adapter = new FlysystemCloudinaryAdapter($cloudinary);
    }
}
