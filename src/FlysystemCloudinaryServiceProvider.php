<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Cloudinary;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
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
            $adapter = new FlysystemCloudinaryAdapter( new Cloudinary($config));

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
