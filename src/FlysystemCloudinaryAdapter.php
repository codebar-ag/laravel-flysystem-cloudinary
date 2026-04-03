<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\BadRequest;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Exception\RateLimited;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
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
        if ($this->meta === false) {
            throw UnableToWriteFile::atLocation($path, 'Upload failed.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeStream(string $path, $resource, Config $config): void
    {
        $this->meta = $this->upload($path, $resource, $config);
        if ($this->meta === false) {
            throw UnableToWriteFile::atLocation($path, 'Upload failed.');
        }
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
        $tempFile = null;
        if (is_string($body)) {
            $tempFile = tmpfile();
            if ($tempFile === false) {
                return false;
            }
            if (fwrite($tempFile, $body) === false) {
                return false;
            }
            if (rewind($tempFile) === false) {
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
        $sourceLogical = trim($path, '/');
        $destLogical = trim($newpath, '/');
        $prefixedSource = $this->ensureFolderIsPrefixed($sourceLogical);
        $prefixedDest = $this->ensureFolderIsPrefixed($destLogical);

        $metaRead = $this->readObject($prefixedSource);

        if ($metaRead === false) {
            $this->copied = false;
            throw UnableToCopyFile::fromLocationTo($sourceLogical, $destLogical);
        }

        $metaUpload = $this->upload($prefixedDest, $metaRead['contents'], $config);

        if ($metaUpload === false) {
            $this->copied = false;
            throw UnableToCopyFile::fromLocationTo($sourceLogical, $destLogical);
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
        $logical = trim($path, '/');
        $prefixed = $this->ensureFolderIsPrefixed($logical);

        $this->deleted = $this->destroy($prefixed);
        if (! $this->deleted) {
            throw UnableToDeleteFile::atLocation(
                $logical,
                'Cloudinary destroy did not succeed for this resource type or path.'
            );
        }
    }

    protected function destroy(string $path): bool
    {
        $options = [
            'invalidate' => true,
        ];

        try {
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

            return $response->getArrayCopy()['result'] === 'ok';
        } catch (ApiError) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDir($dirname): bool
    {
        $dirname = $this->ensureFolderIsPrefixed(trim($dirname, '/'));

        $files = $this->listContents($dirname, false);

        foreach ($files as $item) {
            if (! $item->isFile()) {
                continue;
            }
            $logical = $item->path();
            $this->destroy($this->ensureFolderIsPrefixed($logical));
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
        $logical = trim($dirname, '/');
        $prefixed = $this->ensureFolderIsPrefixed($logical);

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->createFolder($prefixed);
        } catch (ApiError|RateLimited) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return [
            'path' => $logical,
            'type' => 'dir',
        ];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $sourceLogical = trim($source, '/');
        $destLogical = trim($destination, '/');
        $prefixedSource = $this->ensureFolderIsPrefixed($sourceLogical);
        $prefixedDest = $this->ensureFolderIsPrefixed($destLogical);

        try {
            $this->cloudinary
                ->uploadApi()
                ->rename($prefixedSource, $prefixedDest);
        } catch (NotFound $exception) {
            throw UnableToMoveFile::fromLocationTo($sourceLogical, $destLogical, $exception);
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
        $logical = trim($path, '/');
        $prefixed = $this->ensureFolderIsPrefixed($logical);

        $meta = $this->readObject($prefixed);
        if ($meta === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        return (string) $meta['contents'];
    }

    /**
     * {@inheritDoc}
     */
    public function readStream(string $path)
    {
        $logical = trim($path, '/');
        $prefixed = $this->ensureFolderIsPrefixed($logical);

        $meta = $this->readObject($prefixed);

        if ($meta === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        $tempFile = tmpfile();
        if ($tempFile === false) {
            throw UnableToReadFile::fromLocation($path, 'Could not create temporary stream.');
        }

        if (fwrite($tempFile, $meta['contents']) === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        if (rewind($tempFile) === false) {
            throw UnableToReadFile::fromLocation($path);
        }

        return $tempFile;
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
     *
     * Shallow listing only ($deep is ignored). Cloudinary Admin API does not map cleanly to recursive Flysystem trees.
     *
     * @return iterable<FileAttributes|DirectoryAttributes>
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $directory = $this->ensureFolderIsPrefixed(trim($path, '/'));

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

        $out = [];

        foreach ($responseRawFiles->getArrayCopy()['resources'] ?? [] as $resource) {
            $out[] = $this->toFileAttributes(
                $this->normalizeResponse($resource, $resource['public_id'])
            );
        }

        foreach ($responseImageFiles->getArrayCopy()['resources'] ?? [] as $resource) {
            $out[] = $this->toFileAttributes(
                $this->normalizeResponse($resource, $resource['public_id'])
            );
        }

        foreach ($responseVideoFiles->getArrayCopy()['resources'] ?? [] as $resource) {
            $out[] = $this->toFileAttributes(
                $this->normalizeResponse($resource, $resource['public_id'])
            );
        }

        foreach ($responseDirectories->getArrayCopy()['folders'] ?? [] as $resource) {
            $logicalPath = $this->ensurePrefixedFolderIsRemoved($resource['path']);

            $out[] = new DirectoryAttributes(
                $logicalPath,
                null,
                null,
                isset($resource['name']) ? ['name' => $resource['name']] : []
            );
        }

        return $out;
    }

    private function toFileAttributes(array $normalized): FileAttributes
    {
        $timestamp = $normalized['timestamp'];
        $lastModified = ($timestamp !== false && $timestamp !== null) ? (int) $timestamp : null;

        $extra = array_filter([
            'etag' => $normalized['etag'] ?? null,
            'version' => $normalized['version'] ?? null,
            'versionid' => $normalized['versionid'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $size = $normalized['size'] ?? null;

        return new FileAttributes(
            $normalized['path'],
            is_numeric($size) ? (int) $size : null,
            $normalized['visibility'] ?? 'public',
            $lastModified,
            $normalized['mimetype'] ?? null,
            $extra
        );
    }

    private function getMetadata(string $path, string $type): FileAttributes
    {
        $prefixed = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $result = (array) $this->cloudinary->adminApi()->asset($prefixed);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }

        $attributes = $this->mapToFileAttributes($result);

        // @phpstan-ignore-next-line
        if (! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type);
        }

        return $attributes;
    }

    private function mapToFileAttributes($resource): FileAttributes
    {
        $publicId = $resource['public_id'];
        $logicalPath = $this->ensurePrefixedFolderIsRemoved($publicId);

        return new FileAttributes(
            $logicalPath,
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

    /**
     * Get the URL of an image with optional transformation parameters
     *
     * @return string
     */
    public function getUrl(string|array $path): string|false
    {
        $options = [];

        if (is_array($path)) {
            $options = $path['options'] ?? [];
            $path = $path['path'];
        }

        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            return (string) $this->cloudinary->image($path)->toUrl(implode(',', $options));
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
            'mimetype' => (new FinfoMimeTypeDetector)->detectMimeType($path, $body) ?? 'text/plain',
            'path' => $path,
            'size' => Arr::get($response, 'bytes'),
            'timestamp' => strtotime((string) Arr::get($response, 'created_at')),
            'type' => 'file',
            'version' => Arr::get($response, 'version'),
            'versionid' => Arr::get($response, 'version_id'),
            'visibility' => Arr::get($response, 'access_mode') === 'public' ? 'public' : 'private',
        ];
    }

    public function fileExists(string $path): bool
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $this->cloudinary->adminApi()->asset($path);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    public function directoryExists(string $path): bool
    {
        $path = trim($path, '/');
        $prefixedPath = $this->ensureFolderIsPrefixed($path);
        $pos = strrpos($prefixedPath, '/');
        $needle = $pos === false ? '' : substr($prefixedPath, 0, $pos);

        try {
            $folders = [];
            $response = null;
            do {
                $response = (array) $this->cloudinary->adminApi()->subFolders($needle, [
                    'max_results' => 500,
                    'next_cursor' => $response['next_cursor'] ?? null,
                ]);

                $folders = array_merge($folders, $response['folders'] ?? []);
            } while (! empty($response['next_cursor']));

            foreach ($folders as $folder) {
                if (($folder['path'] ?? '') === $prefixedPath) {
                    return true;
                }
            }
        } catch (Exception) {
            return false;
        }

        return false;
    }

    public function deleteDirectory(string $path): void
    {
        $prefixed = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $this->cloudinary->adminApi()->deleteFolder($prefixed);
        } catch (Throwable $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $prefixed = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $this->cloudinary->adminApi()->createFolder($prefixed);
        } catch (Throwable $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
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
