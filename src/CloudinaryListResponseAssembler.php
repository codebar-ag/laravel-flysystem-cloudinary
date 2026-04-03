<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\RateLimited;
use Cloudinary\Cloudinary;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;

final class CloudinaryListResponseAssembler
{
    public function __construct(
        private readonly Cloudinary $cloudinary,
        private readonly CloudinaryPathNormalizer $paths,
        private readonly CloudinaryResponseMapper $mapper,
        private readonly CloudinaryResponseLogger $logger,
    ) {}

    /**
     * @return list<FileAttributes|DirectoryAttributes>
     */
    public function shallowList(string $directoryPrefixed): array
    {
        $baseOptions = [
            'type' => 'upload',
            'prefix' => $directoryPrefixed,
            'max_results' => 500,
        ];

        try {
            $assetResponses = [];
            foreach (['raw', 'image', 'video'] as $resourceType) {
                $assetResponses[] = $this->cloudinary->adminApi()->assets(
                    $baseOptions + ['resource_type' => $resourceType]
                );
            }

            $responseDirectories = $this->cloudinary->adminApi()->subFolders($directoryPrefixed);
        } catch (RateLimited|ApiError) {
            return [];
        }

        foreach ($assetResponses as $response) {
            $this->logger->log($response);
        }
        $this->logger->log($responseDirectories);

        $out = [];

        foreach ($assetResponses as $response) {
            foreach ($response->getArrayCopy()['resources'] ?? [] as $resource) {
                $out[] = $this->mapper->normalizedToFileAttributes(
                    $this->mapper->normalizeUploadOrExplicit($resource, $resource['public_id'])
                );
            }
        }

        foreach ($responseDirectories->getArrayCopy()['folders'] ?? [] as $resource) {
            $out[] = new DirectoryAttributes(
                $this->paths->logical($resource['path']),
                null,
                null,
                isset($resource['name']) ? ['name' => $resource['name']] : []
            );
        }

        return $out;
    }
}
