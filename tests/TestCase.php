<?php

namespace CodebarAg\FlysystemCloudinary\Tests;

use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
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
