<?php

namespace CodebarAg\FlysystemCloudinary\Tests\Integration;

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\Tests\TestCase;
use Illuminate\Http\Testing\File;
use League\Flysystem\Config;
use League\Flysystem\UnableToMoveFile;

/** @group Integration */
class CloudinaryTest extends TestCase
{
    protected FlysystemCloudinaryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $cloudinary = new Cloudinary([
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->adapter = new FlysystemCloudinaryAdapter($cloudinary);
    }

    /** @test */
    public function it_can_write()
    {
        $publicId = 'file-write-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $this->adapter->write($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($this->adapter->meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_write_stream()
    {
        $publicId = 'file-write-stream-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $this->adapter->writeStream($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($this->adapter->meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_update()
    {
        $publicId = 'file-update-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->update($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_update_stream()
    {
        $publicId = 'file-update-stream-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->updateStream($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    protected function assertUploadResponse(array $meta, string $publicId): void
    {
        $this->assertIsString($meta['contents']);
        $this->assertIsString($meta['etag']);
        $this->assertSame('image/jpeg', $meta['mimetype']);
        $this->assertSame($publicId, $meta['path']);
        $this->assertSame(695, $meta['size']);
        $this->assertIsInt($meta['timestamp']);
        $this->assertSame('file', $meta['type']);
        $this->assertIsInt($meta['version']);
        $this->assertIsString($meta['versionid']);
        $this->assertSame('public', $meta['visibility']);
    }

    /** @test */
    public function it_can_rename()
    {
        $path = 'file-old-path-'.rand();
        $newPath = 'file-new-path-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($path, $fakeImage, new Config());

        $bool = $this->adapter->rename($path, $newPath);

        $this->assertTrue($bool);
        $this->adapter->delete($newPath); // cleanup
    }

    /** @test */
    public function it_does_not_rename_if_file_is_not_found()
    {
        $path = 'file-does-not-exist';
        $newPath = 'file-renamed';

        $bool = $this->adapter->rename($path, $newPath);

        $this->assertFalse($bool);
    }

    /** @test */
    public function it_does_not_rename_if_new_path_already_exists()
    {
        $path = 'file-rename-'.rand();
        $newPath = 'file-already-exists-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($path, $fakeImage, new Config());
        $this->adapter->write($newPath, $fakeImage, new Config());

        $bool = $this->adapter->rename($path, $newPath);

        $this->assertFalse($bool);
        $this->adapter->delete($path); // cleanup
        $this->adapter->delete($newPath); // cleanup
    }

    /** @test */
    public function it_can_copy()
    {
        $path = 'file-old-copy-'.rand();
        $newPath = 'file-new-copy-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($path, $fakeImage, new Config());

        $this->adapter->copy($path, $newPath, new Config());

        $this->assertTrue($this->adapter->fileExists($newPath));
        $this->adapter->delete($path); // cleanup
        $this->adapter->delete($newPath); // cleanup
    }

    /** @test */
    public function it_does_not_copy_if_file_is_not_found()
    {
        $path = 'file-does-not-exist';
        $newPath = 'file-copied';

        $this->adapter->copy($path, $newPath, new Config());

        $this->assertFalse($this->adapter->copied);
    }

    /** @test */
    public function it_can_delete()
    {
        $publicId = 'file-delete-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $this->adapter->delete($publicId);

        $this->assertFalse($this->adapter->fileExists($publicId));
    }

    /** @test */
    public function it_can_delete_a_directory()
    {
        $publicId = 'delete_dir/file-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $bool = $this->adapter->deleteDir('delete_dir');

        $this->assertTrue($bool);
    }

    /** @test */
    public function it_can_create_a_directory()
    {
        $directory = 'directory-'.rand();

        $meta = $this->adapter->createDir($directory, new Config());

        $this->assertSame([
            'path' => $directory,
            'type' => 'dir',
        ], $meta);
        $this->adapter->deleteDir($directory); // cleanup
    }

    /** @test */
    public function it_can_check_if_file_exists()
    {
        $publicId = 'file-has-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $bool = $this->adapter->has($publicId);

        $this->assertTrue($bool);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_check_if_file_does_not_exist()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->has($publicId);

        $this->assertFalse($bool);
    }

    /** @test */
    public function it_can_read()
    {
        $publicId = 'file-read-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $content = $this->adapter->read($publicId);

        $this->assertSame($content, $fakeImage);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_read_stream()
    {
        $publicId = 'file-read-stream-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $meta = $this->adapter->readStream($publicId);

        $this->assertIsResource($meta['stream']);
        $this->assertArrayNotHasKey('contents', $meta);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_does_not_read_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $response = $this->adapter->read($publicId);

        $this->assertEmpty($response);
    }

    /** @test */
    public function it_does_not_read_stream_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->readStream($publicId);

        $this->assertFalse($bool);
    }

    /** @test */
    public function it_can_list_directory_contents()
    {
        $files = $this->adapter->listContents('sandbox');

        $this->assertIsArray($files);
    }

    /** @test */
    public function it_does_get_metadata()
    {
        $publicId = 'file-get-metadata-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $meta = $this->adapter->getMetadata($publicId);

        $this->assertMetadataResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_does_not_get_metadata_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->getMetadata($publicId);

        $this->assertFalse($bool);
    }

    /** @test */
    public function it_does_get_size()
    {
        $publicId = 'file-get-size-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $meta = $this->adapter->getSize($publicId);

        $this->assertMetadataResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_does_not_get_size_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->getSize($publicId);

        $this->assertFalse($bool);
    }

    /** @test */
    public function it_does_get_mimetype()
    {
        $publicId = 'file-get-mimetype-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $meta = $this->adapter->getMimetype($publicId);

        $this->assertMetadataResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_does_not_get_mimetype_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->getMimetype($publicId);

        $this->assertFalse($bool);
    }

    /** @test */
    public function it_does_get_timestamp()
    {
        $publicId = 'file-get-mimetype-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $meta = $this->adapter->getTimestamp($publicId);

        $this->assertMetadataResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_does_not_get_timestamp_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->getTimestamp($publicId);

        $this->assertFalse($bool);
    }

    protected function assertMetadataResponse(array $meta, string $publicId): void
    {
        $this->assertIsString($meta['contents']);
        $this->assertNull($meta['etag']);
        $this->assertSame('image/jpeg', $meta['mimetype']);
        $this->assertSame($publicId, $meta['path']);
        $this->assertSame(695, $meta['size']);
        $this->assertIsInt($meta['timestamp']);
        $this->assertSame('file', $meta['type']);
        $this->assertIsInt($meta['version']);
        $this->assertIsString($meta['versionid']);
        $this->assertSame('public', $meta['visibility']);
    }

    /** @test */
    public function it_does_get_url()
    {
        $publicId = 'file-get-url-'.rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $url = $this->adapter->getUrl($publicId);

        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString($publicId, $url);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_move_file()
    {
        $sourceId = 'source-file-'.rand();
        $source = File::image('black.jpg')->getContent();
        $movedToId = 'moved-file-'.rand();

        $this->assertFalse($this->adapter->fileExists($movedToId));

        $this->adapter->write($sourceId, $source, new Config());
        $this->adapter->move($sourceId, $movedToId, new Config());

        $this->assertFalse($this->adapter->fileExists($sourceId));
        $this->assertTrue($this->adapter->fileExists($movedToId));
    }

    /** @test */
    public function it_cant_move_unexisted_file()
    {
        $sourceId = 'source-file-'.rand();
        $movedToId = 'moved-file-'.rand();

        $this->assertFalse($this->adapter->fileExists($sourceId));
        $this->expectException(UnableToMoveFile::class);
        $this->adapter->move($sourceId, $movedToId, new Config());
    }

    /** @test */
    public function it_can_create_and_delete_directory()
    {
        $directory = 'directory_to_create';
        $this->adapter->createDirectory($directory, new Config());
        
        $this->assertTrue($this->adapter->directoryExists($directory));

        $this->adapter->deleteDirectory($directory);
        $this->assertFalse($this->adapter->directoryExists($directory));
    }
}
