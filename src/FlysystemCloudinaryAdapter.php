<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\BadRequest;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Exception\RateLimited;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use Illuminate\Support\Arr;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;

class FlysystemCloudinaryAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    public function __construct(
        public Cloudinary $cloudinary,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function write($path, $contents, Config $config): array | false
    {
        ray('adapter write');

        return $this->upload($path, $contents);
    }

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, Config $config): array | false
    {
        ray('adapter writeStream');

        return $this->upload($path, $resource);
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $config): array | false
    {
        ray('adapter upload');

        return $this->upload($path, $contents);
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $config): array | false
    {
        ray('adapter updateStream');

        return $this->upload($path, $resource);
    }

    /**
     * Upload an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     *
     * @param string|resource $body
     */
    protected function upload(string $path, $body): array | false
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
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#rename_method
     */
    public function rename($path, $newpath): bool
    {
        ray('adapter rename');

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
        } catch (NotFound | BadRequest) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath): bool
    {
        ray('adapter copy');

        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $newpath = $this->ensureFolderIsPrefixed(trim($newpath, '/'));

        $metaRead = $this->readObject($path);

        if ($metaRead === false) {
            return false;
        }

        $metaUpload = $this->upload($newpath, $metaRead['contents']);

        if ($metaUpload === false) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#destroy_method
     */
    public function delete($path): bool
    {
        ray('adapter delete');

        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path);

        event(new FlysystemCloudinaryResponseLog($response));

        ['result' => $result] = $response->getArrayCopy();

        if ($result === 'not found') {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname): bool
    {
        ray('adapter deleteDir');

        $dirname = $this->ensureFolderIsPrefixed(trim($dirname, '/'));

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->deleteFolder($dirname);
        } catch (ApiError) { // RateLimited?
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function createDir($dirname, Config $config): array | false
    {
        ray('adapter createDir');

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

    /**
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     */
    public function has($path): array | bool | null
    {
        ray('adapter has');

        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $options = [
            'type' => 'upload',
        ];

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);
        } catch (NotFound) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($path): array | false
    {
        ray('adapter read');

        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $meta = $this->readObject($path);

        if ($meta === false) {
            return false;
        }

        return $meta;
    }

    /**
     * @inheritDoc
     */
    public function readStream($path): array | false
    {
        ray('adapter readStream');

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
    protected function readObject(string $path): array | bool
    {
        $options = [
            'type' => 'upload',
        ];

        try {
            /** @var ApiResponse $response */
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);
        } catch (NotFound) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        ['url' => $url] = $response->getArrayCopy();

        $contents = file_get_contents($url);

        if ($contents === false) {
            return false;
        }

        return $this->normalizeResponse($response, $path, $contents);
    }

    /**
     * @inheritDoc
     */
    public function listContents($directory = '', $recursive = false): array
    {
        ray('adapter listContents');

        $directory = $this->ensureFolderIsPrefixed(trim($directory, '/'));

        $options = [
            'type' => 'upload',
            'prefix' => $directory,
        ];

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);
        } catch (RateLimited) {
            return [];
        }

        return array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $response->getArrayCopy()['resources']);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($path): array | false
    {
        ray('adapter getMetadata');

        $meta = $this->readObject($path);

        if ($meta === false) {
            return false;
        }

        return $meta;
    }

    /**
     * @inheritDoc
     */
    public function getSize($path): array | false
    {
        ray('adapter getSize');

        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     */
    public function getMimetype($path): array | false
    {
        ray('adapter getMimetype');

        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp($path): array | false
    {
        ray('adapter getTimestamp');

        return $this->getMetadata($path);
    }

    public function getUrl(string $path): string | false
    {
        ray('adapter getUrl');

        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $options = [
            'type' => 'upload',
        ];

        try {
            /** @var ApiResponse $response */
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);
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

    protected function ensureFolderIsPrefixed(string $path): string
    {
        if (config('flysystem-cloudinary.folder')) {
            $folder = trim(config('flysystem-cloudinary.folder'), '/');

            return "{$folder}/$path";
        }

        return $path;
    }

    /**
     * Normalize the object result array.
     *
     * https://flysystem.thephpleague.com/v1/docs/architecture/
     *
     * @param string|resource|null $body
     */
    protected function normalizeResponse(
        ApiResponse | array $response,
        string $path,
        $body = null,
    ): array {
        return [
            'contents' => $body,
            'etag' => Arr::get($response, 'etag'),
            'mimetype' => Util::guessMimeType($path, $body),
            'path' => $path,
            'size' => Arr::get($response, 'bytes'),
            'timestamp' => strtotime(Arr::get($response, 'created_at')),
            'type' => 'file',
            'version' => Arr::get($response, 'version'),
            'versionid' => Arr::get($response, 'version_id'),
            'visibility' => Arr::get($response, 'access_mode') === 'public' ? 'public' : 'private',
        ];
    }
}
