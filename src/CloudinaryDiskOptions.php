<?php

namespace CodebarAg\FlysystemCloudinary;

use League\Flysystem\Config;

final readonly class CloudinaryDiskOptions
{
    /**
     * @param  array<string, mixed>  $globalUploadOptions
     */
    public function __construct(
        public ?string $folder,
        public ?string $uploadPreset,
        public array $globalUploadOptions,
        public bool $preferSecureUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $diskConfig
     */
    public static function fromDiskAndConfig(array $diskConfig): self
    {
        $publishedOptions = config('flysystem-cloudinary.options', []);

        $options = $diskConfig['options'] ?? null;
        if (! is_array($options)) {
            $options = is_array($publishedOptions) ? $publishedOptions : [];
        }

        return new self(
            folder: self::nullableString($diskConfig['folder'] ?? config('flysystem-cloudinary.folder')),
            uploadPreset: self::nullableString($diskConfig['upload_preset'] ?? config('flysystem-cloudinary.upload_preset')),
            globalUploadOptions: $options,
            preferSecureUrl: (bool) ($diskConfig['secure_url'] ?? config('flysystem-cloudinary.secure_url', true)),
        );
    }

    /**
     * Merge base upload API options with disk + Flysystem {@see Config} overrides.
     *
     * @return array<string, mixed>
     */
    public function uploadOptionsFor(string $logicalPublicId, Config $config): array
    {
        $base = [
            'type' => 'upload',
            'public_id' => $logicalPublicId,
            'invalidate' => true,
            'use_filename' => true,
            'resource_type' => 'auto',
            'unique_filename' => false,
        ];

        $fromDisk = array_filter([
            'folder' => $this->folder,
            'upload_preset' => $this->uploadPreset,
        ], static fn ($v) => $v !== null && $v !== '');

        $merged = array_merge($base, $fromDisk, $this->globalUploadOptions);

        $flyOptions = $config->get('options');
        if (is_array($flyOptions) && $flyOptions !== []) {
            $merged = array_merge($merged, $flyOptions);
        }

        return $merged;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
