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
            $nextCursor = null;
            do {
                $response = (array) $cloudinary->adminApi()->subFolders($needle, [
                    'max_results' => 500,
                    'next_cursor' => $nextCursor,
                ]);

                $folders = array_merge($folders, $response['folders'] ?? []);
                $nextCursor = $response['next_cursor'] ?? null;
            } while (! empty($nextCursor));

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
