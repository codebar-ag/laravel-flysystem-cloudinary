<?php

namespace CodebarAg\Cloudinary;

use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Cloudinary;
use CodebarAg\Cloudinary\Events\CloudinaryResponseLog;
use Illuminate\Support\Str;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class CloudinaryAdapter implements AdapterInterface
{
    public function __construct(
        public Cloudinary $cloudinary,
    ) {
    }

    /** @inheritdoc */
    public function write($path, $contents, Config $config): array | false
    {
        $tmpFile = tmpfile();

        if (fwrite($tmpFile, $contents)) {
            return $this->writeStream($path, $tmpFile, $config);
        }

        return false;
    }

    /** @inheritdoc */
    public function writeStream($path, $resource, Config $config): array | false
    {
        $options = [
            'public_id' => Str::beforeLast($path, '.'),
            'use_filename' => true,
            'unique_filename' => false,
            'resource_type' => 'auto',
            'type' => 'upload',
        ];

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->upload($resource, $options);
        } catch (ApiError) {
            return false;
        }

        event(new CloudinaryResponseLog($response));

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

    /** @inheritdoc */
    public function update($path, $contents, Config $config): array | false
    {
        // TODO: Implement update() method.
    }

    /** @inheritdoc */
    public function updateStream($path, $resource, Config $config): array | false
    {
        // TODO: Implement updateStream() method.
    }

    /** @inheritdoc */
    public function rename($path, $newpath): bool
    {
        // TODO: Implement rename() method.
    }

    /** @inheritdoc */
    public function copy($path, $newpath): bool
    {
        // TODO: Implement copy() method.
    }

    /** @inheritdoc */
    public function delete($path): bool
    {
        // TODO: Implement delete() method.
    }

    /** @inheritdoc */
    public function deleteDir($dirname): bool
    {
        // TODO: Implement deleteDir() method.
    }

    /** @inheritdoc */
    public function createDir($dirname, Config $config): array | false
    {
        // TODO: Implement createDir() method.
    }

    /** @inheritdoc */
    public function setVisibility($path, $visibility): array | false
    {
        // TODO: Implement setVisibility() method.
    }

    /** @inheritdoc */
    public function has($path): array | bool | null
    {
        // TODO: Implement has() method.
    }

    /** @inheritdoc */
    public function read($path): array | false
    {
        // TODO: Implement read() method.
    }

    /** @inheritdoc */
    public function readStream($path): array | false
    {
        // TODO: Implement readStream() method.
    }

    /** @inheritdoc */
    public function listContents($directory = '', $recursive = false): array
    {
        // TODO: Implement listContents() method.
    }

    /** @inheritdoc */
    public function getMetadata($path): array | false
    {
        // TODO: Implement getMetadata() method.
    }

    /** @inheritdoc */
    public function getSize($path): array | false
    {
        // TODO: Implement getSize() method.
    }

    /** @inheritdoc */
    public function getMimetype($path): array | false
    {
        // TODO: Implement getMimetype() method.
    }

    /** @inheritdoc */
    public function getTimestamp($path): array | false
    {
        // TODO: Implement getTimestamp() method.
    }

    /** @inheritdoc */
    public function getVisibility($path): array | false
    {
        // TODO: Implement getVisibility() method.
    }
}
