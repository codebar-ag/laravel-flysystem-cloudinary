<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\BadRequest;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Exception\RateLimited;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Throwable;

class FlysystemCloudinaryAdapter implements FilesystemAdapter
{
    public array|false $meta;

    public bool $copied;

    public bool $deleted;

    private const EXTRA_METADATA_FIELDS = [
        'version',
        'width',
        'height',
        'url',
        'secure_url',
        'next_cursor',
    ];

    public function __construct(
        public Cloudinary $cloudinary,
    ) {
        Configuration::instance($cloudinary->configuration);
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->meta = $this->upload($path, $contents, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function writeStream(string $path, $resource, Config $config): void
    {
        $this->meta = $this->upload($path, $resource, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function update($path, $contents, Config $config): array|false
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStream($path, $resource, Config $config): array|false
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Upload an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     *
     * @param  string|resource  $body
     */
    protected function upload(string $path, $body, Config $config): array|false
    {
        if (is_string($body)) {
            $tempFile = tmpfile();

            if (fwrite($tempFile, $body) === false) {
                return false;
            }
        }

        $path = trim($path, '/');

        $options = [
            'type' => 'upload',
            'public_id' => $path,
            'invalidate' => true,
            'use_filename' => true,
            'resource_type' => 'auto',
            'unique_filename' => false,
        ];

        if (config('flysystem-cloudinary.folder')) {
            $options['folder'] = config('flysystem-cloudinary.folder');
        }

        if (config('flysystem-cloudinary.upload_preset')) {
            $options['upload_preset'] = config('flysystem-cloudinary.upload_preset');
        }

        if (config('flysystem-cloudinary.options')) {
            $options = array_merge($options, config('flysystem-cloudinary.options'));
        }

        if ($config->get('options')) {
            $options = array_merge($options, $config->get('options'));
        }

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->upload($tempFile ?? $body, $options);
        } catch (ApiError) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return $this->normalizeResponse($response, $path, $body);
    }

    /**
     * {@inheritDoc}
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#rename_method
     */
    public function rename($path, $newpath): bool
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $newpath = $this->ensureFolderIsPrefixed(trim($newpath, '/'));

        $options = [
            'invalidate' => true,
        ];

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->rename($path, $newpath, $options);
        } catch (NotFound|BadRequest) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function copy(string $path, string $newpath, Config $config): void
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $newpath = $this->ensureFolderIsPrefixed(trim($newpath, '/'));

        $metaRead = $this->readObject($path);

        if ($metaRead === false) {
            $this->copied = false;

            return;
        }

        $metaUpload = $this->upload($newpath, $metaRead['contents'], $config);

        if ($metaUpload === false) {
            $this->copied = false;

            return;
        }

        $this->copied = true;
    }

    /**
     * {@inheritDoc}
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#destroy_method
     */
    public function delete(string $path): void
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $this->deleted = $this->destroy($path);
    }

    protected function destroy(string $path): bool
    {
        $options = [
            'invalidate' => true,
        ];

        $options['resource_type'] = 'image';
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path, $options);
        event(new FlysystemCloudinaryResponseLog($response));

        if ($response->getArrayCopy()['result'] === 'ok') {
            return true;
        }

        $options['resource_type'] = 'raw';
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path, $options);

        event(new FlysystemCloudinaryResponseLog($response));

        if ($response->getArrayCopy()['result'] === 'ok') {
            return true;
        }

        $options['resource_type'] = 'video';
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path, $options);

        event(new FlysystemCloudinaryResponseLog($response));

        if ($response->getArrayCopy()['result'] === 'ok') {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDir($dirname): bool
    {
        $dirname = $this->ensureFolderIsPrefixed(trim($dirname, '/'));

        $files = $this->listContents($dirname);

        foreach ($files as ['path' => $path]) {
            $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

            $this->destroy($path);
        }

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->deleteFolder($dirname);
        } catch (ApiError|RateLimited) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function createDir($dirname, Config $config): array|false
    {
        $dirname = $this->ensureFolderIsPrefixed(trim($dirname, '/'));

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->createFolder($dirname);
        } catch (RateLimited) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return [
            'path' => $dirname,
            'type' => 'dir',
        ];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->cloudinary
                ->uploadApi()
                ->rename($source, $destination);
        } catch (NotFound $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * {@inheritDoc}
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     */
    public function has($path): array|bool|null
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $this->explicit($path);
        } catch (NotFound) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $path): string
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $contents = file_get_contents(Media::fromParams($path));
        } catch (Exception) {
            $contents = '';
        }

        return (string) $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function readStream($path): array|false /** @phpstan-ignore-line */
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $meta = $this->readObject($path);

        if ($meta === false) {
            return false;
        }

        $tempFile = tmpfile();

        if (fwrite($tempFile, $meta['contents']) === false) {
            return false;
        }

        if (rewind($tempFile) === false) {
            return false;
        }

        unset($meta['contents']);

        $meta['stream'] = $tempFile;

        return $meta;
    }

    /**
     * Read an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     */
    protected function readObject(string $path): array|bool
    {
        try {
            $response = $this->explicit($path);
        } catch (NotFound) {
            return false;
        }

        ['secure_url' => $url] = $response->getArrayCopy();

        try {
            $contents = Http::get($url)->throw()->body();
        } catch (RequestException) {
            return false;
        }

        return $this->normalizeResponse($response, $path, $contents);
    }

    /**
     * {@inheritDoc}
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $directory = $this->ensureFolderIsPrefixed(trim($directory, '/'));

        $options = [
            'type' => 'upload',
            'prefix' => $directory,
            'max_results' => 500,
        ];

        try {
            $options['resource_type'] = 'raw';
            $responseRawFiles = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);

            $options['resource_type'] = 'image';
            $responseImageFiles = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);

            $options['resource_type'] = 'video';
            $responseVideoFiles = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);

            $responseDirectories = $this
                ->cloudinary
                ->adminApi()
                ->subFolders($directory);
        } catch (RateLimited|ApiError) {
            return [];
        }

        event(new FlysystemCloudinaryResponseLog($responseRawFiles));
        event(new FlysystemCloudinaryResponseLog($responseImageFiles));
        event(new FlysystemCloudinaryResponseLog($responseVideoFiles));
        event(new FlysystemCloudinaryResponseLog($responseDirectories));

        $rawFiles = array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $responseRawFiles->getArrayCopy()['resources']);

        $imageFiles = array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $responseImageFiles->getArrayCopy()['resources']);

        $videoFiles = array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $responseVideoFiles->getArrayCopy()['resources']);

        $folders = array_map(function (array $resource) {
            $path = $this->ensurePrefixedFolderIsRemoved($resource['path']);

            return [
                'type' => 'dir',
                'path' => $path,
                'name' => $resource['name'],
            ];
        }, $responseDirectories->getArrayCopy()['folders']);

        return [
            ...$rawFiles,
            ...$imageFiles,
            ...$videoFiles,
            ...$folders,
        ];
    }

    private function getMetadata(string $path, string $type): FileAttributes
    {
        try {
            $result = (array) $this->cloudinary->adminApi()->asset($path);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }

        $attributes = $this->mapToFileAttributes($result);

        if (! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type);
        }

        return $attributes;
    }

    private function mapToFileAttributes($resource): FileAttributes
    {
        return new FileAttributes(
            $resource['public_id'],
            (int) $resource['bytes'],
            'public',
            (int) strtotime($resource['created_at']),
            (string) sprintf('%s/%s', $resource['resource_type'], $resource['format']),
            $this->extractExtraMetadata((array) $resource)
        );
    }

    private function extractExtraMetadata(array $metadata): array
    {
        $extracted = [];

        foreach (self::EXTRA_METADATA_FIELDS as $field) {
            if (isset($metadata[$field]) && $metadata[$field] !== '') {
                $extracted[$field] = $metadata[$field];
            }
        }

        return $extracted;
    }

    public function getUrl(string $path): string|false
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            return (string) $this->cloudinary->image($path)->toUrl();
        } catch (NotFound) {
            return false;
        }
    }

    public function getUrlViaRequest(string $path): string|false
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $response = $this->explicit($path);
        } catch (NotFound) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        [
            'url' => $url,
            'secure_url' => $secure_url,
        ] = $response->getArrayCopy();

        if (config('flysystem-cloudinary.secure_url')) {
            return $secure_url;
        }

        return $url;
    }

    protected function explicit(string $path): ApiResponse
    {
        $options = [
            'type' => 'upload',
        ];

        try {
            $options['resource_type'] = 'image';
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);

            event(new FlysystemCloudinaryResponseLog($response));

            return $response;
        } catch (NotFound) {
        }

        try {
            $options['resource_type'] = 'raw';
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);

            event(new FlysystemCloudinaryResponseLog($response));

            return $response;
        } catch (NotFound) {
        }

        try {
            $options['resource_type'] = 'video';
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);

            event(new FlysystemCloudinaryResponseLog($response));

            return $response;
        } catch (NotFound $e) {
            throw $e;
        }
    }

    protected function ensureFolderIsPrefixed(string $path): string
    {
        if (config('flysystem-cloudinary.folder')) {
            $folder = trim(config('flysystem-cloudinary.folder'), '/');

            return "{$folder}/$path";
        }

        return $path;
    }

    protected function ensurePrefixedFolderIsRemoved(string $path): string
    {
        if (config('flysystem-cloudinary.folder')) {
            $prefix = config('flysystem-cloudinary.folder').'/';

            return Str::of($path)
                ->after($prefix)
                ->__toString();
        }

        return $path;
    }

    /**
     * Normalize the object result array.
     *
     * https://flysystem.thephpleague.com/v1/docs/architecture/
     *
     * @param  string|resource|null  $body
     */
    protected function normalizeResponse(
        ApiResponse|array $response,
        string $path,
        $body = null,
    ): array {
        $path = $this->ensurePrefixedFolderIsRemoved($path);

        return [
            'contents' => $body,
            'etag' => Arr::get($response, 'etag'),
            'mimetype' => (new FinfoMimeTypeDetector())->detectMimeType($path, $body) ?? 'text/plain',
            'path' => $path,
            'size' => Arr::get($response, 'bytes'),
            'timestamp' => strtotime(Arr::get($response, 'created_at')),
            'type' => 'file',
            'version' => Arr::get($response, 'version'),
            'versionid' => Arr::get($response, 'version_id'),
            'visibility' => Arr::get($response, 'access_mode') === 'public' ? 'public' : 'private',
        ];
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->cloudinary->adminApi()->asset($path);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function directoryExists(string $path): bool
    {
        $folders = [];
        $needle = substr($path, 0, strripos($path, '/'));

        $response = null;
        do {
            $response = (array) $this->cloudinary->adminApi()->subFolders($needle, [
                'max_results' => 4,
                'next_cursor' => isset($response['next_cursor']) ? $response['next_cursor'] : null, /** @phpstan-ignore-line */
            ]);

            $folders = array_merge($folders, $response['folders']); /** @phpstan-ignore-line */
        } while (array_key_exists('next_cursor', $response) && ! is_null($response['next_cursor'])); /** @phpstan-ignore-line */
        $folders_found = array_filter(
            $folders,
            function ($e) use ($path) {
                return $e['path'] == $path;
            }
        );

        return count($folders_found) > 0;
    }

    public function deleteDirectory(string $path): void
    {
        $this->cloudinary->adminApi()->deleteFolder($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->cloudinary->adminApi()->createFolder($path);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    public function getMimetype($path): string
    {
        return $this->mimeType($path)->mimeType();
    }

    public function getVisibility($path): string
    {
        return $this->visibility($path)->visibility();
    }

    public function getTimestamp($path): int
    {
        return $this->lastModified($path)->lastModified();
    }

    public function getSize($path): int
    {
        return $this->fileSize($path)->fileSize();
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_VISIBILITY);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_MIME_TYPE);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE);
    }
}
