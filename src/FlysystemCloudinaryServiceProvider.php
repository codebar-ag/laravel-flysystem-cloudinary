<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Cloudinary;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FlysystemCloudinaryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-flysystem-cloudinary')
            ->hasConfigFile('flysystem-cloudinary');
    }

    public function bootingPackage(): void
    {
        Storage::extend('cloudinary', function (Application $app, array $config) {
            $cloudinary = new Cloudinary($config);

            return new Filesystem(new FlysystemCloudinaryAdapter($cloudinary));
        });
    }
}
