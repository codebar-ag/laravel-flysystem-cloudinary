<img src="https://banners.beyondco.de/Laravel%20Cloudinary.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-cloudinary&pattern=circuitBoard&style=style_2&description=An+opinionated+way+to+integrate+Cloudinary+with+Laravel&md=1&showWatermark=0&fontSize=150px&images=cloud&widths=500&heights=500">

## 💡 What is Cloudinary?

wip

## 🛠 Requirements

- PHP: `^8.0`
- Laravel: `^8.12`
- Cloudinary Account

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
                'secure' => env('CLOUDINARY_URL_SECURE', true),
            ],
        ],

    ],
```

Add the following environment variables to your `.env` file:

```shell
FILESYSTEM_DRIVER=cloudinary

CLOUDINARY_CLOUD_NAME=your-cloud-name
CLOUDINARY_API_KEY=your-api-key
CLOUDINARY_API_SECRET=your-api-secret
```

## 🔧 Configuration file

You can publish the config file with:

```shell
php artisan vendor:publish --tag="flysystem-cloudinary"
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
    */

    'folder' => env('CLOUDINARY_FOLDER'),

];
```

## 🚧 Testing

Copy your own phpunit.xml-file.
```shell
cp phpunit.xml.dist phpunit.xml
```

Modify environment variables in the phpunit.xml-file:
```xml
wip
```

Run the tests:
```shell
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## ✏️ Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## 🧑‍💻 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 🙏 Credits

- [Ruslan Steiger](https://github.com/SuddenlyRust)
- [All Contributors](../../contributors)
- [Skeleton Repository from Spatie](https://github.com/spatie/package-skeleton-laravel)
- [Laravel Package Training from Spatie](https://spatie.be/videos/laravel-package-training)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
