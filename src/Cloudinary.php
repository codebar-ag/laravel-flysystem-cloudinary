<?php

namespace CodebarAg\Cloudinary;

class Cloudinary
{
    const RESOURCE_TYPE_AUTO = 'auto';
    const RESOURCE_TYPE_IMAGE = 'image';
    const RESOURCE_TYPE_RAW = 'raw';
    const RESOURCE_TYPE_VIDEO = 'video';

    const RESOURCE_TYPES = [
        self::RESOURCE_TYPE_AUTO,
        self::RESOURCE_TYPE_IMAGE,
        self::RESOURCE_TYPE_RAW,
        self::RESOURCE_TYPE_VIDEO,
    ];

    public static function getResourceType(string $type = self::RESOURCE_TYPE_AUTO): string
    {
        return $type;
    }

    public static function getBasename(string $path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }
    public static function getPath(string $path, $folder = null): string
    {
        $folder = isset($folder) ?? config('cloudinary.folder');

        //Filename with extension
        $basename =

        //Path of the directory
        $dirname = pathinfo($path, PATHINFO_DIRNAME);

        //Filename without extension
        $filename = pathinfo($path, PATHINFO_FILENAME);

        // for raw resource type use basename as filename
        if ($this->getResourceType($path) === 'raw') {
            $filename = $basename;
        }

        // if directory exists prepends with dirname
        return $dirname != '.' ? "{$dirname}/{$filename}" : $filename;
    }
}
