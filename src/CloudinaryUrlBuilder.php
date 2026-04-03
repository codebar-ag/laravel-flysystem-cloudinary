<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;

final class CloudinaryUrlBuilder
{
    public function __construct(
        private readonly Cloudinary $cloudinary,
        private readonly CloudinaryPathNormalizer $paths,
        private readonly CloudinaryDiskOptions $diskOptions,
        private readonly CloudinaryResourceOperations $resourceOps,
        private readonly CloudinaryResponseLogger $logger,
    ) {}

    public function deliveryUrl(string|array $path): string|false
    {
        $options = [];
        if (is_array($path)) {
            $options = $path['options'] ?? [];
            $path = $path['path'];
        }

        $prefixed = $this->paths->prefixed(trim((string) $path, '/'));

        try {
            return (string) $this->cloudinary->image($prefixed)->toUrl(implode(',', $options));
        } catch (NotFound) {
            return false;
        }
    }

    public function urlViaExplicit(string $path): string|false
    {
        $prefixed = $this->paths->prefixed(trim($path, '/'));

        try {
            $response = $this->resourceOps->explicit($this->cloudinary, $prefixed);
        } catch (NotFound) {
            return false;
        }

        $this->logger->log($response);

        [
            'url' => $url,
            'secure_url' => $secureUrl,
        ] = $response->getArrayCopy();

        return $this->diskOptions->preferSecureUrl ? $secureUrl : $url;
    }
}
