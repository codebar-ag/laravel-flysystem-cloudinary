<?php

namespace CodebarAg\FlysystemCloudinary;

final class CloudinaryStringUploadSource
{
    /**
     * Write string contents to a temporary file Cloudinary can read by path.
     * Caller must {@see fclose()} the handle when upload has finished.
     *
     * @return array{path: string, handle: resource}|false
     */
    public static function create(string $contents): array|false
    {
        $handle = tmpfile();
        if ($handle === false) {
            return false;
        }

        if (fwrite($handle, $contents) === false) {
            fclose($handle);

            return false;
        }

        if (rewind($handle) === false) {
            fclose($handle);

            return false;
        }

        $uri = stream_get_meta_data($handle)['uri'] ?? '';
        if ($uri === '') {
            fclose($handle);

            return false;
        }

        return ['path' => $uri, 'handle' => $handle];
    }
}
