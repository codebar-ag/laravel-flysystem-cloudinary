<?php

namespace CodebarAg\FlysystemCloudinary\Tests\Feature;

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\Tests\TestCase;
use Illuminate\Http\Testing\File;
use League\Flysystem\Config;

class FlysystemCloudinaryAdapterTest extends TestCase
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

//    protected function getPackageProviders($app): array
//    {
//        return [
//            FlysystemCloudinaryServiceProvider::class,
//        ];
//    }

    /** @test */
    public function it_can_write()
    {
        $publicId = 'file-write-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->write($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_write_stream()
    {
        $publicId = 'file-write-stream-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->writeStream($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_update()
    {
        $publicId = 'file-update-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->update($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_update_stream()
    {
        $publicId = 'file-update-stream-' . rand();
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
        $path = 'file-old-path-' . rand();
        $newPath = 'file-new-path-' . rand();
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
        $path = 'file-rename-' . rand();
        $newPath = 'file-already-exists-' . rand();
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
        $this->markTestSkipped('We need to test read first');
    }

    /** @test */
    public function it_can_delete()
    {
        $publicId = 'file-delete-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();
        $this->adapter->write($publicId, $fakeImage, new Config());

        $bool = $this->adapter->delete($publicId);

        $this->assertTrue($bool);
    }

    /** @test */
    public function it_does_not_delete_if_file_is_not_found()
    {
        $publicId = 'file-does-not-exist';

        $bool = $this->adapter->delete($publicId);

        $this->assertFalse($bool);
    }
}
