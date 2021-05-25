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
        $publicId = 'image-write';
        $fakeImage = File::image('black.jpg')->getContent();

        $meta = $this->adapter->write($publicId, $fakeImage, new Config());

        $this->assertSame('file', $meta['type']);
        $this->assertSame($publicId, $meta['path']);
        $this->assertIsString($meta['contents']);
        $this->assertSame('public', $meta['visibility']);
        $this->assertIsInt($meta['timestamp']);
        $this->assertSame(695, $meta['size']);
        $this->assertSame(695, $meta['bytes']);

        // cleanup
        $this->adapter->delete('image-write');
    }
}
