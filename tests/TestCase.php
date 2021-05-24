<?php

namespace CodebarAg\Cloudinary\Tests;

use CodebarAg\Cloudinary\CloudinaryServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{

    protected function getPackageProviders($app): array
    {
        return [
            CloudinaryServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
