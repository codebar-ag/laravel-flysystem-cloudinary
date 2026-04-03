# Changelog

All notable changes to `laravel-cloudinary` will be documented in this file.

## 13.0.0 - 2026-04-03

- Laravel 13 support
- PHP 8.3, 8.4, and 8.5 support (PHP 8.2 dropped)
- Development tooling: Orchestra Testbench 11, Pest 4, Larastan 3.9
- Packagist `dev-main` branch alias `13.x-dev` for Composer until `v13.0.0` is tagged
- **Flysystem 3:** `listContents()` now returns `FileAttributes` / `DirectoryAttributes` so `Storage::files()`, `Storage::directories()`, and related APIs work (fixes [#64](https://github.com/codebar-ag/laravel-flysystem-cloudinary/issues/64), [#80](https://github.com/codebar-ag/laravel-flysystem-cloudinary/issues/80))
- **Flysystem 3:** `read()` / `readStream()` throw `UnableToReadFile` on failure; `readStream()` returns a `resource`; `copy()` throws `UnableToCopyFile`; `delete()` throws `UnableToDeleteFile` when destroy fails; `createDirectory()` / `deleteDirectory()` throw the corresponding Flysystem exceptions
- Apply configured folder prefix to `move()`, `createDirectory()`, `deleteDirectory()`, and fix `directoryExists()` for root-level paths
- Fix string uploads: rewind temp stream after `fwrite()` before Cloudinary `upload()`
- `createDir()` catches `ApiError` as well as `RateLimited`
- Composer scripts: `analyse` (PHPStan), `test` / `test-coverage` via Pest with `--exclude-group=integration`; `format` uses Pint
- Integration tests: `integration` group, skip when placeholder Cloudinary env is used
- **Adapter internals:** Extracted `CloudinaryPathNormalizer`, `CloudinaryDiskOptions`, `CloudinaryResponseMapper`, `CloudinaryResourceOperations`, and `CloudinaryResponseLogger`; disk options (folder, upload preset, merge options, secure URL preference) are resolved from the Laravel disk config in `FlysystemCloudinaryServiceProvider` with fallback to `config('flysystem-cloudinary.*')` when the adapter is constructed without disk options
- **API:** Added `lastUploadMetadata()`, `lastCopySucceeded()`, and `lastDeleteSucceeded()`; public `$meta`, `$copied`, and `$deleted` remain for backward compatibility
- **Adapter slimming:** `CloudinaryStringUploadSource`, `CloudinaryAdminFolderLocator`, `CloudinaryUrlBuilder`, `CloudinaryListResponseAssembler`, `CloudinaryDiskOptions::uploadOptionsFor()`, and `Concerns\InteractsWithCloudinaryMetadata` further shrink `FlysystemCloudinaryAdapter` and move branching out of the adapter file
- **Security:** `phpunit.xml` no longer ships real Cloudinary API secrets; default env is placeholder-only. Live API integration tests run only when real `CLOUDINARY_*` values are exported (see `tests/cloudinary_integration.php`)
- **Tests:** Dedicated Pest files per collaborator (`CloudinaryResourceOperations`, `CloudinaryResponseMapper`, `CloudinaryResponseLogger`, `CloudinaryUrlBuilder`, `CloudinaryListResponseAssembler`, `CloudinaryAdminFolderLocator`, `CloudinaryDiskOptions`, `FlysystemCloudinaryResponseLog`, `FlysystemCloudinaryServiceProvider`); extra integration cases for missing files, directories, and rename

## 2.0.0 - 2022-11-20

laravel-flysystem v3 upgrade:

## 1.1.0 - 2022-03-16

- Laravel 9 Support

## 1.0.4 - 2021-12-30

- Bumped Version "friendsofphp/php-cs-fixer": "3.*",

## 1.0.3 - 2021-06-30

- 🐛 Bug Fix: Delete file works with all resource types.

## 1.0.3 - 2021-06-30

- 🐛 Bug Fix: Delete file works with all resource types.

## 1.0.2 - 2021-06-24

- 🐛 Bug Fix: Folder prefix working correct now
- 🐛 Bug Fix: List files now works with raw and image files

## 1.0.1 - 2021-06-23

- 🐛 Bug Fix: List folders

## 1.0.0 - 2021-06-03

- Stable release

## 0.3.0 - 2021-06-02

- ⚠️ BREAKING CHANGE: Renamed environment variable
  `CLOUDINARY_URL_SECURE` to `CLOUDINARY_SECURE_URL`
- The `getUrl` method now returns a secure url

## 0.2.0 - 2021-06-01

- Added additional configuration with folder and preset

## 0.1.0 - 2021-05-26

- Stable release
- Added tests

## 0.0.0 - 2021-05-24

- Initial release
