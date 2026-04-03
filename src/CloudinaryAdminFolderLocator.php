<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Cloudinary;
use Throwable;

final class CloudinaryAdminFolderLocator
{
    public function folderExists(Cloudinary $cloudinary, string $prefixedPath): bool
    {
        $pos = strrpos($prefixedPath, '/');
        $needle = $pos === false ? '' : substr($prefixedPath, 0, $pos);

        try {
            $folders = [];
            $response = null;
            do {
                $response = (array) $cloudinary->adminApi()->subFolders($needle, [
                    'max_results' => 500,
                    'next_cursor' => $response['next_cursor'] ?? null,
                ]);

                $folders = array_merge($folders, $response['folders'] ?? []);
            } while (! empty($response['next_cursor']));

            foreach ($folders as $folder) {
                if (($folder['path'] ?? '') === $prefixedPath) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }
}
