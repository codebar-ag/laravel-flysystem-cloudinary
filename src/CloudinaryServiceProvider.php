<?php

namespace CodebarAg\Cloudinary;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CloudinaryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-cloudinary')
            ->hasConfigFile('cloudinary');
    }

    public function bootingPackage(): void
    {
        Storage::extend('cloudinary', function (Application $app, array $config) {
            $cloudinary = new \Cloudinary\Cloudinary($config);

            return new Filesystem(new CloudinaryAdapter($cloudinary));
        });
    }
}
