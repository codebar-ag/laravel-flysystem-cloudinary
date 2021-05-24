<img src="https://banners.beyondco.de/Laravel%20Cloudinary.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-cloudinary&pattern=circuitBoard&style=style_2&description=An+opinionated+way+to+integrate+Cloudinary+with+Laravel&md=1&showWatermark=0&fontSize=150px&images=cloud&widths=500&heights=500">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebar-ag/laravel-twilio-verify.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-twilio-verify)
[![Total Downloads](https://img.shields.io/packagist/dt/codebar-ag/laravel-twilio-verify.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-twilio-verify)
[![run-tests](https://github.com/codebar-ag/laravel-twilio-verify/actions/workflows/run-tests.yml/badge.svg)](https://github.com/codebar-ag/laravel-twilio-verify/actions/workflows/run-tests.yml)
[![Psalm](https://github.com/codebar-ag/laravel-twilio-verify/actions/workflows/psalm.yml/badge.svg)](https://github.com/codebar-ag/laravel-twilio-verify/actions/workflows/psalm.yml)
[![Check & fix styling](https://github.com/codebar-ag/laravel-twilio-verify/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/codebar-ag/laravel-twilio-verify/actions/workflows/php-cs-fixer.yml)

This package was developed to give you a quick start to communicate start and
check a verification with Twilio Verify.

⚠️ This package is not designed as a replacement of the official 
[Twilio REST API](https://www.twilio.com/docs/verify/api). 
See the documentation if you need further functionality. ⚠️

## 💡 What is Twilio Verify?
An elegant third-party integration to validate users with SMS, Voice, Email and
Push. Add verification to any step of your user‘s journey with a single API.

## 🛠 Requirements

- PHP: `^8.0`
- Laravel: `^8.12`
- Cloudinary Account

## ⚙️ Installation

You can install the package via composer:

```bash
composer require codebar-ag/laravel-twilio-verify
```

Add the following environment variables to your `.env` file:

```bash
TWILIO_ACCOUNT_SID=ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
TWILIO_AUTH_TOKEN=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
TWILIO_SERVICE_SID=VAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

## 🏗 Usage

```php
use CodebarAg\Cloudinary\Facades\Cloudinary;

/**
 * Start a new SMS verification to the phone number.
 */
$verificationStart = Cloudinary::start(to: '+12085059915');

/**
 * Check a verification code.
 */
$verificationCheck = Cloudinary::check(to: '+12085059915', code: '1337');
```

### 🔢 Verification Code Limits

- The generated code is valid for (10 minutes)[ https://www.twilio.com/docs/verify/api/rate-limits-and-timeouts].
- You have attempted to send the verification code more than 5 times and have
  (reached the limit)[https://www.twilio.com/docs/api/errors/60203].
  
## 🏋️ DTO showcase

```php
CodebarAg\Cloudinary\DTO\SendCodeAttempt {
  +time: Illuminate\Support\Carbon                      // Carbon
  +channel: "sms"                                       // string
  +attempt_sid: "VLMn1NOnmIVWMITO4FbVGxNmEMJ72KKaB2"    // string
}
```

```php 
CodebarAg\TwilioVerify\DTO\Lookup {
  +carrier: CodebarAg\TwilioVerify\DTO\Carrier {
    +error_code: null           // ?string
    +name: "Carrier Name"       // string
    +mobile_country_code: "310" // string
    +mobile_network_code: "150" // string
    +type: "150"                // string
  }
}
```

```php
CodebarAg\Cloudinary\DTO\VerificationStart
  +sid: "VEkEJNNkLugY4hietPDbcqUUZz3G5NoTTZ"            // string
  +service_sid: "VAssMsB84NdN0aJJceYsExX1223qAmrubx"    // string
  +account_sid: "ACizUsoInA3dbKR5LA9tOqqA0O3NFSHSNc"    // string
  +to: "+41795555825"                                   // string
  +channel: "sms"                                       // string
  +status: "pending"                                    // string
  +valid: false                                         // bool
  +created_at: Illuminate\Support\Carbon                // Carbon
  +updated_at: Illuminate\Support\Carbon                // Carbon
  +lookup: CodebarAg\Cloudinary\DTO\Lookup {...}      // Lookup
  +send_code_attempts: Illuminate\Support\Collection {  // Collection
      0 => CodebarAg\Cloudinary\DTO\SendCodeAttempt {
        +time: Illuminate\Support\Carbon                    // Carbon
        +channel: "sms"                                     // string
        +attempt_sid: "VLTvj9jhh76cI78Hc1x0c3UORWJwwqVeTN"  // string
      }
    ]
  }
  +url: "https://verify.twilio.com/v2/Services/VAssMsB84NdN0aJJceYsExX1223qAmrubx/Verifications" // string
}
```

```php
CodebarAg\Cloudinary\DTO\VerificationCheck {
  +sid: "VEvRzh4hPUqmAjeC6li092VNT0yfd23lag"            // string
  +service_sid: "VAxSR0Wq91djjG9PAYtrtjt11f0I4lqdwa"    // string
  +account_sid: "ACcI5zbEYvLr0vPIUTQzWkTpP5DPqTCYDK"    // string
  +to: "+41795555825"                                   // string
  +channel: "sms"                                       // string
  +status: "approved"                                   // string
  +valid: true                                          // bool
  +created_at: Illuminate\Support\Carbon                // Carbon
  +updated_at: Illuminate\Support\Carbon                // Carbon
}
```

## 🔧 Configuration file

You can publish the config file with:
```bash
php artisan vendor:publish --provider="CodebarAg\TwilioVerify\TwilioVerifyServiceProvider" --tag="laravel-twilio-verify-config"
```

This is the contents of the published config file:

```php

return [

    /*
    |--------------------------------------------------------------------------
    | Twilio Verify Configuration
    |--------------------------------------------------------------------------
    |
    | You can find your Account SID and Auth Token in the Console Dashboard.
    | Additionally you should create a new Verify service and paste it in
    | here. Afterwards you are ready to communicate with Twilio Verify.
    |
    */

    'url' => env('TWILIO_URL', 'https://verify.twilio.com/v2/Services'),
    'account_sid' => env('TWILIO_ACCOUNT_SID', 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'),
    'auth_token' => env('TWILIO_AUTH_TOKEN', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'),
    'service_sid' => env('TWILIO_SERVICE_SID', 'VAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'),

];
```

## ✨ Events

Following events are fired:

```php 
use CodebarAg\TwilioVerify\Events\TwilioVerifyResponseLog;

// Log each response from the Twilio REST API.
TwilioVerifyResponseLog::class => [
    //
],
```

## 🚧 Testing

Copy your own phpunit.xml-file.
```bash
cp phpunit.xml.dist phpunit.xml
```

Modify environment variables in the phpunit.xml-file:
```xml
<env name="TWILIO_ACCOUNT_SID" value="ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"/>
<env name="TWILIO_AUTH_TOKEN" value="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"/>
<env name="TWILIO_SERVICE_SID" value="VAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"/>
```

Run the tests:
```bash
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
