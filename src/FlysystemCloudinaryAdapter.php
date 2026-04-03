<?php

namespace CodebarAg\FlysystemCloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\BadRequest;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Exception\RateLimited;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use CodebarAg\FlysystemCloudinary\Concerns\InteractsWithCloudinaryMetadata;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use ReflectionProperty;
use Throwable;

class FlysystemCloudinaryAdapter implements FilesystemAdapter
{
    use InteractsWithCloudinaryMetadata;

    /**
     * Populated after {@see write()} / {@see writeStream()}. Prefer {@see lastUploadMetadata()} for new code.
     * False before any upload on this adapter instance.
     *
     * @var array<string, mixed>|false
     */
    public array|false $meta = false;

    /** Set after {@see copy()}. Prefer {@see lastCopySucceeded()} for new code. */
    public bool $copied = false;

    /** Set after {@see delete()}. Prefer {@see lastDeleteSucceeded()} for new code. */
    public bool $deleted = false;

    private readonly CloudinaryDiskOptions $diskOptions;

    private readonly CloudinaryPathNormalizer $paths;

    private readonly CloudinaryResponseMapper $mapper;

    private readonly CloudinaryResponseLogger $logger;

    private readonly CloudinaryResourceOperations $resourceOps;

    private readonly CloudinaryAdminFolderLocator $folderLocator;

    private readonly CloudinaryUrlBuilder $urlBuilder;

    private readonly CloudinaryListResponseAssembler $listAssembler;

    public function __construct(
        public Cloudinary $cloudinary,
        ?CloudinaryDiskOptions $diskOptions = null,
    ) {
        $this->diskOptions = $diskOptions ?? CloudinaryDiskOptions::fromDiskAndConfig([]);

        $this->paths = new CloudinaryPathNormalizer($this->diskOptions->folder);
        $this->mapper = new CloudinaryResponseMapper($this->paths);
        $this->logger = new CloudinaryResponseLogger;
        $this->resourceOps = new CloudinaryResourceOperations($this->logger);
        $this->folderLocator = new CloudinaryAdminFolderLocator;
        $this->urlBuilder = new CloudinaryUrlBuilder(
            $this->cloudinary,
            $this->paths,
            $this->diskOptions,
            $this->resourceOps,
            $this->logger
        );
        $this->listAssembler = new CloudinaryListResponseAssembler(
            $this->cloudinary,
            $this->paths,
            $this->mapper,
            $this->logger
        );

        // cloudinary/cloudinary_php: read initialization without touching an uninitialized typed property.
        // Coupled to Cloudinary::$configuration (public in 3.x); a major SDK change should fail tests on upgrade.
        $configurationProperty = new ReflectionProperty(Cloudinary::class, 'configuration');
        if ($configurationProperty->isInitialized($cloudinary)) {
            Configuration::instance($cloudinary->configuration);
        }
    }

    protected function cloudinaryForMetadata(): Cloudinary
    {
        return $this->cloudinary;
    }

    protected function pathsForMetadata(): CloudinaryPathNormalizer
    {
        return $this->paths;
    }

    protected function mapperForMetadata(): CloudinaryResponseMapper
    {
        return $this->mapper;
    }

    /**
     * @return array<string, mixed>|false
     */
    public function lastUploadMetadata(): array|false
    {
        return $this->meta;
    }

    public function lastCopySucceeded(): bool
    {
        return $this->copied;
    }

    public function lastDeleteSucceeded(): bool
    {
        return $this->deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->writeAndSetMeta($path, $contents, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function writeStream(string $path, $resource, Config $config): void
    {
        $this->writeAndSetMeta($path, $resource, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function update($path, $contents, Config $config): array|false
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStream($path, $resource, Config $config): array|false
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Upload an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     *
     * @param  string|resource  $body
     * @return array<string, mixed>|false
     */
    protected function upload(string $path, $body, Config $config): array|false
    {
        $temporaryFileHandle = null;
        $fileForUpload = $body;

        if (is_string($body)) {
            $source = CloudinaryStringUploadSource::create($body);
            if ($source === false) {
                return false;
            }
            $fileForUpload = $source['path'];
            $temporaryFileHandle = $source['handle'];
        }

        $logicalPath = trim($path, '/');

        try {
            $response = $this->cloudinary
                ->uploadApi()
                ->upload($fileForUpload, $this->diskOptions->uploadOptionsFor($logicalPath, $config));
        } catch (ApiError) {
            return false;
        } finally {
            if ($temporaryFileHandle !== null) {
                fclose($temporaryFileHandle);
            }
        }

        $this->logger->log($response);

        return $this->mapper->normalizeUploadOrExplicit($response, $logicalPath, $body);
    }

    /**
     * {@inheritDoc}
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#rename_method
     */
    public function rename($path, $newpath): bool
    {
        $from = $this->paths->prefixed(trim($path, '/'));
        $to = $this->paths->prefixed(trim($newpath, '/'));

        try {
            $response = $this->cloudinary
                ->uploadApi()
                ->rename($from, $to, ['invalidate' => true]);
        } catch (NotFound|BadRequest) {
            return false;
        }

        $this->logger->log($response);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function copy(string $path, string $newpath, Config $config): void
    {
        $sourceLogical = trim($path, '/');
        $destLogical = trim($newpath, '/');
        $prefixedSource = $this->paths->prefixed($sourceLogical);

        $metaRead = $this->readObject($prefixedSource);
        if ($metaRead === false) {
            $this->copied = false;
            throw UnableToCopyFile::fromLocationTo($sourceLogical, $destLogical);
        }

        $metaUpload = $this->upload($destLogical, $metaRead['contents'], $config);
        if ($metaUpload === false) {
            $this->copied = false;
            throw UnableToCopyFile::fromLocationTo($sourceLogical, $destLogical);
        }

        $this->copied = true;
    }

    /**
     * {@inheritDoc}
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#destroy_method
     */
    public function delete(string $path): void
    {
        $logical = trim($path, '/');
        $prefixed = $this->paths->prefixed($logical);

        $this->deleted = $this->resourceOps->destroy($this->cloudinary, $prefixed);
        if (! $this->deleted) {
            throw UnableToDeleteFile::atLocation(
                $logical,
                'Cloudinary destroy did not succeed for this resource type or path.'
            );
        }
    }

    protected function destroy(string $path): bool
    {
        return $this->resourceOps->destroy($this->cloudinary, $path);
    }

    /**
     * Shallow listing: destroys each listed file so Admin API can delete a non-empty folder.
     */
    private function destroyListedFilesInDirectory(string $logicalDir): void
    {
        foreach ($this->listContents($logicalDir, false) as $item) {
            if (! $item->isFile()) {
                continue;
            }
            $this->resourceOps->destroy($this->cloudinary, $this->paths->prefixed($item->path()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDir($dirname): bool
    {
        $logicalDir = trim($dirname, '/');
        $prefixedDir = $this->paths->prefixed($logicalDir);

        $this->destroyListedFilesInDirectory($logicalDir);

        try {
            $response = $this->cloudinary->adminApi()->deleteFolder($prefixedDir);
        } catch (ApiError|RateLimited) {
            return false;
        }

        $this->logger->log($response);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function createDir($dirname, Config $config): array|false
    {
        $logical = trim($dirname, '/');
        $prefixed = $this->paths->prefixed($logical);

        try {
            $response = $this->cloudinary->adminApi()->createFolder($prefixed);
        } catch (ApiError|RateLimited) {
            return false;
        }

        $this->logger->log($response);

        return [
            'path' => $logical,
            'type' => 'dir',
        ];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $sourceLogical = trim($source, '/');
        $destLogical = trim($destination, '/');
        $prefixedSource = $this->paths->prefixed($sourceLogical);
        $prefixedDest = $this->paths->prefixed($destLogical);

        try {
            $this->cloudinary->uploadApi()->rename($prefixedSource, $prefixedDest, ['invalidate' => true]);
        } catch (NotFound $exception) {
            throw UnableToMoveFile::fromLocationTo($sourceLogical, $destLogical, $exception);
        }
    }

    /**
     * {@inheritDoc}
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     */
    public function has($path): array|bool|null
    {
        $prefixed = $this->paths->prefixed(trim($path, '/'));

        try {
            $this->resourceOps->explicit($this->cloudinary, $prefixed);
        } catch (NotFound) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $path): string
    {
        $logical = trim($path, '/');

        return (string) $this->readPrefixedOrFail($this->paths->prefixed($logical), $path)['contents'];
    }

    /**
     * {@inheritDoc}
     */
    public function readStream(string $path)
    {
        $logical = trim($path, '/');
        $meta = $this->readPrefixedOrFail($this->paths->prefixed($logical), $path);

        return $this->contentsToTempStream((string) $meta['contents'], $path);
    }

    /**
     * Read an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     *
     * @return array<string, mixed>|false
     */
    protected function readObject(string $path): array|false
    {
        try {
            $response = $this->resourceOps->explicit($this->cloudinary, $path);
        } catch (NotFound) {
            return false;
        }

        ['secure_url' => $url] = $response->getArrayCopy();

        try {
            $contents = Http::get($url)->throw()->body();
        } catch (RequestException) {
            return false;
        }

        return $this->mapper->normalizeUploadOrExplicit($response, $path, $contents);
    }

    /**
     * {@inheritDoc}
     *
     * Shallow listing only ($deep is ignored). Cloudinary Admin API does not map cleanly to recursive Flysystem trees.
     *
     * @return iterable<FileAttributes|DirectoryAttributes>
     */
    public function listContents(string $path, bool $deep): iterable
    {
        return $this->listAssembler->shallowList(
            $this->paths->prefixed(trim($path, '/'))
        );
    }

    public function getUrl(string|array $path): string|false
    {
        return $this->urlBuilder->deliveryUrl($path);
    }

    public function getUrlViaRequest(string $path): string|false
    {
        return $this->urlBuilder->urlViaExplicit($path);
    }

    protected function explicit(string $path): ApiResponse
    {
        return $this->resourceOps->explicit($this->cloudinary, $path);
    }

    /**
     * Normalize the object result array.
     *
     * https://flysystem.thephpleague.com/v1/docs/architecture/
     *
     * @param  string|resource|null  $body
     * @return array<string, mixed>
     */
    protected function normalizeResponse(
        ApiResponse|array $response,
        string $path,
        $body = null,
    ): array {
        return $this->mapper->normalizeUploadOrExplicit($response, $path, $body);
    }

    public function fileExists(string $path): bool
    {
        $prefixed = $this->paths->prefixed(trim($path, '/'));

        try {
            $this->cloudinary->adminApi()->asset($prefixed);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public function directoryExists(string $path): bool
    {
        return $this->folderLocator->folderExists(
            $this->cloudinary,
            $this->paths->prefixed(trim($path, '/'))
        );
    }

    public function deleteDirectory(string $path): void
    {
        $logicalDir = trim($path, '/');
        $prefixedDir = $this->paths->prefixed($logicalDir);

        $this->destroyListedFilesInDirectory($logicalDir);

        try {
            $response = $this->cloudinary->adminApi()->deleteFolder($prefixedDir);
        } catch (Throwable $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }

        $this->logger->log($response);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $prefixed = $this->paths->prefixed(trim($path, '/'));

        try {
            $this->cloudinary->adminApi()->createFolder($prefixed);
        } catch (Throwable $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    public function getMimetype($path): string
    {
        return $this->mimeType($path)->mimeType();
    }

    public function getVisibility($path): string
    {
        return $this->visibility($path)->visibility();
    }

    public function getTimestamp($path): int
    {
        return $this->lastModified($path)->lastModified();
    }

    public function getSize($path): int
    {
        return $this->fileSize($path)->fileSize();
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_VISIBILITY);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_MIME_TYPE);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->getMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE);
    }

    private function writeAndSetMeta(string $path, mixed $body, Config $config): void
    {
        $this->meta = $this->upload($path, $body, $config);
        if ($this->meta === false) {
            throw UnableToWriteFile::atLocation($path, 'Upload failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readPrefixedOrFail(string $prefixedPath, string $errorPath): array
    {
        $meta = $this->readObject($prefixedPath);
        if ($meta === false) {
            throw UnableToReadFile::fromLocation($errorPath);
        }

        return $meta;
    }

    private function contentsToTempStream(string $contents, string $errorPath)
    {
        $tempFile = tmpfile();
        if ($tempFile === false) {
            throw UnableToReadFile::fromLocation($errorPath, 'Could not create temporary stream.');
        }
        if (fwrite($tempFile, $contents) === false) {
            throw UnableToReadFile::fromLocation($errorPath);
        }
        if (rewind($tempFile) === false) {
            throw UnableToReadFile::fromLocation($errorPath);
        }

        return $tempFile;
    }
}
