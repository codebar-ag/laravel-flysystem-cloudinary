<img src="https://banners.beyondco.de/Laravel%20Flysystem%20Cloudinary.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-flysystem-cloudinary&pattern=circuitBoard&style=style_2&description=An+opinionated+way+to+integrate+Cloudinary+with+the+Laravel+filesystem&md=1&showWatermark=0&fontSize=150px&images=cloud&widths=500&heights=500">

## 💡 What is Cloudinary?

Cloudinary is basically a fantastic way to store and serve assets like images
or videos. You can upload your full resolution image to Cloudinary, and they
handle the optimization for you. The only thing you have to do is to add
additional parameters to your url 😉

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
                'secure' => (bool) env('CLOUDINARY_SECURE_URL', true),
            ],
        ],

    ],
```

Add the following environment variables to your `.env` file:

```shell
FILESYSTEM_DRIVER=cloudinary

CLOUDINARY_CLOUD_NAME=my-cloud-name
CLOUDINARY_API_KEY=my-api-key
CLOUDINARY_API_SECRET=my-api-secret
```

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

To customize the name of the stored file, you may use the `storeAs` methods
of the `Image` field:

```php
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Image;

Image::make('Image')
    ->disk('cloudinary')
    ->storeAs(function (Request $request) {
        return sha1($request->image->getClientOriginalName());
    }),
```

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

```
use Illuminate\Support\Facades\Storage;

Storage::disk('cloudinary')->getUrl('meow');
```

This should increase the trust to store and retrieve your assets from the
correct folder.

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

];
```

## 🚧 Testing

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
- [Sebastian Fix](https://github.com/StanBarrows)
- [All Contributors](../../contributors)
- [Skeleton Repository from Spatie](https://github.com/spatie/package-skeleton-laravel)
- [Laravel Package Training from Spatie](https://spatie.be/videos/laravel-package-training)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
