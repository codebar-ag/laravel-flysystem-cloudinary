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
        $publicId = 'file-name-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->write($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_write_stream()
    {
        $publicId = 'file-name-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->writeStream($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_update()
    {
        $publicId = 'file-name-' . rand();
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->update($publicId, $fakeImage, new Config());

        $this->assertUploadResponse($meta, $publicId);
        $this->adapter->delete($publicId); // cleanup
    }

    /** @test */
    public function it_can_update_stream()
    {
        $publicId = 'file-name-' . rand();
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
}
