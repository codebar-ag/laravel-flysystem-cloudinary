<?php

namespace CodebarAg\FlysystemCloudinary\Concerns;

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseMapper;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Throwable;

trait InteractsWithCloudinaryMetadata
{
    abstract protected function cloudinaryForMetadata(): Cloudinary;

    abstract protected function pathsForMetadata(): CloudinaryPathNormalizer;

    abstract protected function mapperForMetadata(): CloudinaryResponseMapper;

    private function getMetadata(string $path, string $type): FileAttributes
    {
        $prefixed = $this->pathsForMetadata()->prefixed(trim($path, '/'));

        try {
            $result = (array) $this->cloudinaryForMetadata()->adminApi()->asset($prefixed);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }

        return $this->mapperForMetadata()->adminAssetToFileAttributes($result);
    }
}
