<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;

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
        $tmpFile = tmpfile();

        if (fwrite($tmpFile, $contents)) {
            return $this->writeStream($path, $tmpFile, $config);
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     */
    public function writeStream($path, $resource, Config $config): array | false
    {
        $path = ltrim($path, '/');

        $options = [
            'type' => 'upload',
            'public_id' => $path,
            'use_filename' => true,
            'resource_type' => 'auto',
            'unique_filename' => false,
        ];

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->upload($resource, $options);
        } catch (ApiError) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        [
            'bytes' => $bytes,
            'version' => $version,
            'created_at' => $created_at,
        ] = $response->getArrayCopy();

        return [
            'path' => $path,
            'size' => $bytes,
            'type' => 'file',
            'version' => $version,
            'timestamp' => strtotime($created_at),
        ];
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $config): array | false
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $config): array | false
    {
        return $this->write($path, $resource, $config);
    }

    /**
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#rename_method
     */
    public function rename($path, $newpath): bool
    {
        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->rename($path, $newpath);
        } catch (NotFound) {
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
        $object = $this->read($path);

        if ($object === false) {
            return false;
        }

        $write = $this->write(
            $newpath,
            $object['contents'],
            resolve(Config::class),
        );

        if ($write === false) {
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
        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->destroy($path);
        } catch (NotFound) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname): bool
    {
        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->deleteFolder($dirname);
        } catch (ApiError) {
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
        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->createFolder($dirname);
        } catch (ApiError) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return [
            'path' => ltrim($dirname, '/'),
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
        $object = $this->readStream($path);

        if ($object === false) {
            return false;
        }

        return [
            'contents' => $object['stream'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function readStream($path): array | false
    {
        $url = $this->getUrl($path);

        $contents = file_get_contents($url);

        if ($contents === false) {
            return false;
        }

        return [
            'stream' => $contents,
        ];
    }

    /**
     * @inheritDoc
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $options = [
            'type' => 'upload',
            'prefix' => $directory,
        ];

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);
        } catch (ApiError) {
            return [];
        }

        ['resources' => $resources] = $response->getArrayCopy();

        return array_map(function ($resource) {
            return [
                'path' => $resource['public_id'],
                'size' => $resource['bytes'],
                'type' => 'file',
                'version' => $resource['version'],
                'timestamp' => strtotime($resource['created_at']),
            ];
        }, $resources);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($path): array | false
    {
        // TODO: Implement getMetadata() method.
    }

    /**
     * @inheritDoc
     */
    public function getSize($path): array | false
    {
        // TODO: Implement getSize() method.
    }

    /**
     * @inheritDoc
     */
    public function getMimetype($path): array | false
    {
        // TODO: Implement getMimetype() method.
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp($path): array | false
    {
        // TODO: Implement getTimestamp() method.
    }

    public function getUrl(string $path): string
    {
        $options = [
            'type' => 'upload',
        ];

        /** @var ApiResponse $response */
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->explicit($path, $options);

        event(new FlysystemCloudinaryResponseLog($response));

        ['url' => $url] = $response->getArrayCopy();

        return $url;
    }
}
