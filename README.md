<img src="https://banners.beyondco.de/Laravel%20Flysystem%20Cloudinary.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-flysystem-cloudinary&pattern=circuitBoard&style=style_2&description=An+opinionated+way+to+integrate+Cloudinary+with+the+Laravel+filesystem&md=1&showWatermark=0&fontSize=150px&images=cloud&widths=500&heights=500">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebar-ag/laravel-flysystem-cloudinary.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-flysystem-cloudinary)
[![Total Downloads](https://img.shields.io/packagist/dt/codebar-ag/laravel-flysystem-cloudinary.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-flysystem-cloudinary)
[![GitHub-Tests](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/run-tests.yml)
[![GitHub Code Style](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/fix-php-code-style-issues.yml)
[![PHPStan](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/phpstan.yml/badge.svg)](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/phpstan.yml)
[![Dependency Review](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/dependency-review.yml/badge.svg)](https://github.com/codebar-ag/laravel-flysystem-cloudinary/actions/workflows/dependency-review.yml)

## 💡 What is Cloudinary?

Cloudinary is basically a fantastic way to store and serve assets like images
or videos. You can upload your full resolution image to Cloudinary, and they
handle the optimization for you. The only thing you have to do is to add
additional parameters to your url 😉

## 🛠 Requirements

- Cloudinary Account

| Package 	 | PHP 	       | Laravel 	 | Flysystem 	 |
|-----------|-------------|-----------|-------------|
| v13.0     | 8.3.*–8.5.* | 13.x      | 3.x         |
| v12.0     | ^8.2 - ^8.4 | 12.x      | 3.x         |
| v11.0     | ^8.2 - ^8.3 | 11.x      | 3.0         |
| v4.0      | ^8.2 - ^8.3 | 11.x      | 3.0         |
| v3.0      | 8.2         | 10.x      | 3.0         |
| v2.0 	    | 8.1 	       | 9.x 	     | 3.0 	       |
| v1.0 	    | 8.0 	       | 8.x 	     | 1.1 	       |


## ⚙️ Installation

You can install the package via composer:

```shell
composer require codebar-ag/laravel-flysystem-cloudinary
```

Add the following disk to your filesystem "disks" list in your `filesystems.php`
configuration:

```php
    'disks' => [
        //

        'cloudinary' => [
            'driver' => 'cloudinary',
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'url' => [
                'secure' => (bool) env('CLOUDINARY_SECURE_URL', true),
            ],
        ],

    ],
```

Add the following environment variables to your `.env` file:

```shell
FILESYSTEM_DISK=cloudinary

CLOUDINARY_CLOUD_NAME=my-cloud-name
CLOUDINARY_API_KEY=my-api-key
CLOUDINARY_API_SECRET=my-api-secret
```

Older Laravel apps may still use `FILESYSTEM_DRIVER`; Laravel 9+ prefers `FILESYSTEM_DISK`.

## Flysystem 3 and Laravel 13

This package registers a **League Flysystem v3** adapter with Laravel’s `Storage` facade.

- **Exceptions:** On failure, `read` / `readStream` throw `UnableToReadFile`; `copy` throws `UnableToCopyFile`; `delete` throws `UnableToDeleteFile`; `createDirectory` / `deleteDirectory` throw `UnableToCreateDirectory` / `UnableToDeleteDirectory` (see [CHANGELOG](CHANGELOG.md)).
- **`deleteDirectory`:** Cloudinary’s Admin API only deletes **empty** folders. The adapter first destroys **shallow-listed files** under the logical path, then calls `delete_folder`—aligned with the legacy `deleteDir()` behaviour. Listing is shallow; deeply nested trees may need extra steps depending on how assets are organised.
- **`listContents`:** **Shallow** listing only; the `$deep` argument is ignored. Each Admin API `assets` call uses `max_results` => 500 **without** `next_cursor` pagination, so very large prefixes may not return a complete list.
- **`write` / `writeStream` vs `update` / `updateStream`:** Only `write` and `writeStream` set `lastUploadMetadata()` and the public `$meta` property. `update` and `updateStream` return the normalized metadata `array` from the upload (or `false` on failure) but **do not** update `lastUploadMetadata()`—it keeps the value from the last `write` / `writeStream`. Use the return value of `update` / `updateStream` when you need fresh metadata.
- **Other helpers:** `lastCopySucceeded()` and `lastDeleteSucceeded()` (and legacy public `$copied` / `$deleted`) reflect the outcome of the latest `copy` / `delete` calls on this adapter instance.

### Cloudinary folder modes

This adapter lists assets with the Admin API using **public ID `prefix`** and manages folders with **`subFolders` / `create_folder` / `delete_folder`**, which matches **legacy fixed folder mode** and typical public-ID paths. If your Cloudinary product environment uses **dynamic folder mode** only, some behaviours may differ; see [Folder modes](https://cloudinary.com/documentation/folder_modes) and the [Admin API](https://cloudinary.com/documentation/admin_api#folders).

### Continuous integration and integration tests

The test suite includes optional **integration** tests that call the live Cloudinary API. They run only when `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, and `CLOUDINARY_API_SECRET` are set to real values (for example via GitHub Actions secrets). The default `composer test` command excludes the `integration` group; run `vendor/bin/pest` without `--exclude-group` to include them locally.

## 🏗 File extension problem

Let's look at the following example:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->put('cat.jpg', $contents);
```

This will generate following URL with double extensions:

```
https://res.cloudinary.com/my-cloud-name/image/upload/v1/cat.jpg.jpg
```

To prevent this you should store your images without the file extension:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->put('cat', $contents);
```

This is now much better:

```
https://res.cloudinary.com/my-cloud-name/image/upload/v1/cat.jpg
```

### 🪐 How to use with Nova

We have a package for use with Laravel
Nova: [Laravel Flysystem Cloudinary Nova](https://github.com/codebar-ag/laravel-flysystem-cloudinary-nova)

## 🗂 How to use folder prefix

Imagine the following example. We have different clients but want to store the
assets in the same Cloudinary account. Normally we have to prefix every path
with the correct client folder name. Fortunately, the package helps us here.
We can configure a folder in our environment file like this:

```shell
CLOUDINARY_FOLDER=client_cat
```

Now all our assets will be prefixed with the `client_cat/` folder. When we
store following image:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->put('meow', $contents);
```

It will produce following URL:

```
https://res.cloudinary.com/my-cloud-name/image/upload/v1/client_cat/meow.jpg
```

In the Media Library it is stored in `client_cat/meow` and you can retrieve
the image with `meow`:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->getAdapter()->getUrl('meow');
```

You can use Cloudinary tranformation and options when retrieving the image:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->getAdapter()->getUrl([
    'path' => 'meow',
    'options' => ['w_250', 'h_250', 'c_thumb'],
]);
```

You can find all options in
the [official documentation](https://cloudinary.com/documentation/transformation_reference)


This should increase the trust to store and retrieve your assets from the
correct folder.

## 🔋 Rate limit gotchas

All files in Cloudinary are stored with a resource type. There are three kinds
of it: `image`, `raw` and `video`. For example if we want to check if a video
exists, we need to make up to 3 requests. Every type needs to be checked on
their own with a separate request.

Keep this in mind because the admin API is rate limited to 500 calls per hour.

The package does check in following sequence:

- `image` ➡️ `raw` ➡️ `video`

## ⚙️ Optional Parameters

Cloudinary has a lot of optional parameters to customize the upload.
You can find all options in
the [official documentation](https://cloudinary.com/documentation/image_upload_api_reference#upload_optional_parameters)
optional parameters section.

You can pass all parameters as an array to the `put`  method:

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->put('meow', $contents, [
    'options' => [
        'notification_url' => 'https://mysite.example.com/notify_endpoint',
        'async' => true,
    ],
]);
```

`Note: if you find yourself using the same parameters for all requests, you should consider adding them to the config file. (see below)`

## 🔧 Configuration file

You can publish the config file with:

```shell
php artisan vendor:publish --tag="flysystem-cloudinary-config"
```

This is the contents of the published config file:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Upload Preset
    |--------------------------------------------------------------------------
    |
    | Upload preset allow you to define the default behavior for all your
    | assets. They have precedence over client-side upload parameters.
    | You can define your upload preset in your cloudinary settings.
    |
    */

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Folder
    |--------------------------------------------------------------------------
    |
    | An optional folder name where the uploaded asset will be stored. The
    | public ID contains the full path of the uploaded asset, including
    | the folder name. This is very useful to prefix assets directly.
    |
    */

    'folder' => env('CLOUDINARY_FOLDER'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Secure URL
    |--------------------------------------------------------------------------
    |
    | This value determines that the asset delivery is forced to use HTTPS
    | URLs. If disabled all your assets will be delivered as HTTP URLs.
    | Please do not use unsecure URLs in your production application.
    |
    */

    'secure_url' => (bool) env('CLOUDINARY_SECURE_URL', true),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Global Upload Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the upload options that will be applied to all
    | your assets. This will be merged with the options that you may
    | define in the `Storage::disk('cloudinary')` call.
    |
    */

    'options' => [
        // 'async' => true,
    ],
];
```

## 🚧 Testing and static analysis

Default test run (Pest, **excludes** the `integration` group that calls the live Cloudinary API):

```shell
composer test
```

PHPStan with Larastan:

```shell
composer analyse
```

Pest with code coverage (also excludes `integration`):

```shell
composer test-coverage
```

Apply the project code style (Laravel Pint):

```shell
composer format
```

To run **all** tests including integration tests, use real `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, and `CLOUDINARY_API_SECRET` in the environment, then:

```shell
vendor/bin/pest
```

The same credentials can be supplied as GitHub Actions secrets for CI (see [.github/workflows/run-tests.yml](.github/workflows/run-tests.yml)).

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## ✏️ Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## 🧑‍💻 Security Vulnerabilities

Please see [.github/SECURITY.md](.github/SECURITY.md) for how to report security vulnerabilities.

## 🙏 Credits

- [Ruslan Steiger](https://github.com/SuddenlyRust)
- [Sebastian Fix](https://github.com/StanBarrows)
- [All Contributors](../../contributors)
- [Skeleton Repository from Spatie](https://github.com/spatie/package-skeleton-laravel)
- [Laravel Package Training from Spatie](https://spatie.be/videos/laravel-package-training)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
