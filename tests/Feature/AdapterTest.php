<?php

namespace CodebarAg\FlysystemCloudinary\Tests\Feature;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\Events\FlysystemCloudinaryResponseLog;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use CodebarAg\FlysystemCloudinary\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
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

    /** @test */
    public function it_can_write_stream()
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

        $meta = $adapter->writeStream($publicId, $contents, new Config());

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

    /** @test */
    public function it_can_update()
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

        $meta = $adapter->update($publicId, $contents, new Config());

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

    /** @test */
    public function it_can_update_stream()
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

        $meta = $adapter->updateStream($publicId, $contents, new Config());

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

    /** @test */
    public function it_can_rename()
    {
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->rename')->once()->andReturn(new ApiResponse([], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $bool = $adapter->rename('::old-path::', '::new-path::');

        $this->assertTrue($bool);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_copy()
    {
        Http::fake();
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'secure_url' => '::url::',
            ], []));
            $mock->shouldReceive('uploadApi->upload')->once()->andReturn(new ApiResponse([], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $bool = $adapter->copy('::from-path::', '::to-path::');

        $this->assertTrue($bool);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 2);
    }

    /** @test */
    public function it_can_delete()
    {
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->destroy')->once()->andReturn(new ApiResponse([
                'result' => 'ok',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $bool = $adapter->delete('::path::');

        $this->assertTrue($bool);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_delete_a_directory()
    {
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('adminApi->assets')->once()->andReturn(new ApiResponse([
                'resources' => [],
            ], []));
            $mock->shouldReceive('adminApi->deleteFolder')->once()->andReturn(new ApiResponse([], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $bool = $adapter->deleteDir('::path::');

        $this->assertTrue($bool);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 2);
    }

    /** @test */
    public function it_can_create_a_directory()
    {
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('adminApi->createFolder')->once()->andReturn(new ApiResponse([], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->createDir('::path::', new Config());

        $this->assertSame([
            'path' => '::path::',
            'type' => 'dir',
        ], $meta);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_check_if_file_exists()
    {
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $bool = $adapter->has('::path::');

        $this->assertTrue($bool);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_read()
    {
        Http::fake(['*' => Http::response('::content::')]);
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'secure_url' => '::url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->read('::path::');

        $this->assertSame('::content::', $meta['contents']);
        $this->assertArrayNotHasKey('stream', $meta);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_read_stream()
    {
        Http::fake();
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'secure_url' => '::url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->readStream('::path::');

        $this->assertIsResource($meta['stream']);
        $this->assertArrayNotHasKey('contents', $meta);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_list_directory_contents()
    {
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('adminApi->assets')->once()->andReturn(new ApiResponse([
                'resources' => [],
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $files = $adapter->listContents('::path::');

        $this->assertSame([], $files);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_get_metadata()
    {
        Http::fake();
        $publicId = '::file-path::';
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) use ($publicId) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'public_id' => $publicId,
                'secure_url' => '::url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->getMetadata($publicId);

        $this->assertSame($publicId, $meta['path']);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_get_size()
    {
        Http::fake();
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'bytes' => 789,
                'secure_url' => '::url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->getSize('::path::');

        $this->assertSame(789, $meta['size']);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_get_mimetype()
    {
        Http::fake();
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'secure_url' => '::url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->getMimetype('::path::');

        $this->assertSame('text/plain', $meta['mimetype']);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_get_timestamp()
    {
        Http::fake();
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'created_at' => '2021-10-10T10:10:10Z',
                'secure_url' => '::url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $meta = $adapter->getTimestamp('::path::');

        $this->assertSame(1633860610, $meta['timestamp']);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }

    /** @test */
    public function it_can_get_url()
    {
        Http::fake();
        $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
            $mock->shouldReceive('uploadApi->explicit')->once()->andReturn(new ApiResponse([
                'url' => '::url::',
                'secure_url' => '::secure-url::',
            ], []));
        });
        $adapter = new FlysystemCloudinaryAdapter($mock);

        $url = $adapter->getUrl('::path::');

        $this->assertSame('::secure-url::', $url);
        Event::assertDispatched(FlysystemCloudinaryResponseLog::class, 1);
    }
}
