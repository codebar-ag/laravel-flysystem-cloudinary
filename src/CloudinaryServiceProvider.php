<?php

namespace CodebarAg\Cloudinary;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Symfony\Component\Filesystem\Filesystem;

class CloudinaryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-cloudinary')
            ->hasConfigFile('cloudinary');

        Storage::extend('cloudinary', function ($app, $config) {
            return new Filesystem(new CloudinaryAdapter($client));
        });
    }
}
