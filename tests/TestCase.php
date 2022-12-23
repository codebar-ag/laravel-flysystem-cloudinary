<?php

namespace CodebarAg\FlysystemCloudinary\Tests;

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public static FlysystemCloudinaryAdapter $cloudinaryAdapter;

    protected function getPackageProviders($app): array
    {
        return [
            FlysystemCloudinaryServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }

    public static function setUpBeforeClass(): void
    {
        $cloudinary = new Cloudinary([
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'url' => [
                'secure' => true,
            ],
        ]);

        self::$cloudinaryAdapter = new FlysystemCloudinaryAdapter($cloudinary);
    }
}
