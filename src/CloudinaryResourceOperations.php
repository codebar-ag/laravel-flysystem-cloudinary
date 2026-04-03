<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;

final class CloudinaryResourceOperations
{
    private const RESOURCE_TYPES = ['image', 'raw', 'video'];

    public function __construct(
        private readonly CloudinaryResponseLogger $logger,
    ) {}

    /**
     * @throws NotFound When the asset does not exist for any resource type.
     */
    public function explicit(Cloudinary $cloudinary, string $prefixedPublicId): ApiResponse
    {
        $options = ['type' => 'upload'];
        $lastNotFound = null;

        foreach (self::RESOURCE_TYPES as $resourceType) {
            $options['resource_type'] = $resourceType;

            try {
                $response = $cloudinary->uploadApi()->explicit($prefixedPublicId, $options);
                $this->logger->log($response);

                return $response;
            } catch (NotFound $exception) {
                $lastNotFound = $exception;
            }
        }

        throw $lastNotFound;
    }

    public function destroy(Cloudinary $cloudinary, string $prefixedPublicId): bool
    {
        $baseOptions = ['invalidate' => true];

        try {
            foreach (self::RESOURCE_TYPES as $resourceType) {
                $options = $baseOptions + ['resource_type' => $resourceType];
                $response = $cloudinary->uploadApi()->destroy($prefixedPublicId, $options);
                $this->logger->log($response);

                if (($response->getArrayCopy()['result'] ?? '') === 'ok') {
                    return true;
                }
            }

            return false;
        } catch (ApiError) {
            return false;
        }
    }
}
