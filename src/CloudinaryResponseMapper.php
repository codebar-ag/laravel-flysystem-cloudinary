<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Illuminate\Support\Arr;
use League\Flysystem\FileAttributes;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

final class CloudinaryResponseMapper
{
    private const EXTRA_METADATA_FIELDS = [
        'version',
        'width',
        'height',
        'url',
        'secure_url',
        'next_cursor',
    ];

    private readonly FinfoMimeTypeDetector $mimeDetector;

    public function __construct(
        private readonly CloudinaryPathNormalizer $paths,
    ) {
        $this->mimeDetector = new FinfoMimeTypeDetector;
    }

    /**
     * @param  string|resource|null  $body
     * @return array<string, mixed>
     */
    public function normalizeUploadOrExplicit(
        ApiResponse|array $response,
        string $path,
        $body = null,
    ): array {
        $logicalPath = $this->paths->logical($path);

        return [
            'contents' => $body,
            'etag' => Arr::get($response, 'etag'),
            'mimetype' => $this->mimeDetector->detectMimeType($logicalPath, $body) ?? 'text/plain',
            'path' => $logicalPath,
            'size' => Arr::get($response, 'bytes'),
            'timestamp' => strtotime((string) Arr::get($response, 'created_at')),
            'type' => 'file',
            'version' => Arr::get($response, 'version'),
            'versionid' => Arr::get($response, 'version_id'),
            'visibility' => Arr::get($response, 'access_mode') === 'public' ? 'public' : 'private',
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    public function normalizedToFileAttributes(array $normalized): FileAttributes
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

    /**
     * @param  array<string, mixed>  $resource
     */
    public function adminAssetToFileAttributes(array $resource): FileAttributes
    {
        $publicId = $resource['public_id'];
        $logicalPath = $this->paths->logical($publicId);

        return new FileAttributes(
            $logicalPath,
            (int) $resource['bytes'],
            'public',
            (int) strtotime($resource['created_at']),
            (string) sprintf('%s/%s', $resource['resource_type'], $resource['format']),
            $this->extractExtraMetadata($resource)
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function extractExtraMetadata(array $metadata): array
    {
        $extracted = [];

        foreach (self::EXTRA_METADATA_FIELDS as $field) {
            if (isset($metadata[$field]) && $metadata[$field] !== '') {
                $extracted[$field] = $metadata[$field];
            }
        }

        return $extracted;
    }
}
