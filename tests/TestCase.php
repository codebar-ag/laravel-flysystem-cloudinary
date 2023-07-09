<?php

namespace CodebarAg\FlysystemCloudinary\Tests;

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
}
