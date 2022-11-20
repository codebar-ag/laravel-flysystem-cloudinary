<?php

namespace CodebarAg\FlysystemCloudinary\Tests\Feature;

use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use League\Flysystem\Config;
use Mockery\MockInterface;

class AdapterTest extends TestCase
{
    public FlysystemCloudinaryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }


    /** @test */
    public function it_can_write()
    {
        $publicId = '::file-path::';
        $contents = '::file-contents::';
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
            $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([
                'public_id' => $publicId,
                'version' => 123456,
                'version_id' => '::version-id::',
                'created_at' => '2021-10-10T10:10:10Z',
                'bytes' => 789,
                'etag' => '::etag::',
                'access_mode' => 'public',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->write($publicId, $contents, new Config());

        $this->assertSame($contents, $meta['contents']);
        $this->assertSame('::etag::', $meta['etag']);
        $this->assertSame('text/plain', $meta['mimetype']);
        $this->assertSame($publicId, $meta['path']);
        $this->assertSame(789, $meta['size']);
        $this->assertSame(1633860610, $meta['timestamp']);
        $this->assertSame('file', $meta['type']);
        $this->assertSame(123456, $meta['version']);
        $this->assertSame('::version-id::', $meta['versionid']);
        $this->assertSame('public', $meta['visibility']);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }


}
