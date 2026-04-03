<?php

namespace CodebarAg\FlysystemCloudinary;

use Illuminate\Support\Str;

final class CloudinaryPathNormalizer
{
    public function __construct(
        private readonly ?string $folder,
    ) {}

    public function prefixed(string $path): string
    {
        $path = trim(trim($path), '/');
        if ($this->folder === null || $this->folder === '') {
            return $path;
        }

        $folder = trim(trim($this->folder), '/');

        if ($path === '') {
            return $folder;
        }

        return "{$folder}/{$path}";
    }

    public function logical(string $path): string
    {
        if ($this->folder === null || $this->folder === '') {
            return $path;
        }

        $folder = trim(trim($this->folder), '/');
        $prefix = $folder.'/';

        return Str::of($path)->after($prefix)->__toString();
    }
}
