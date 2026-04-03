<?php

use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\FlysystemCloudinaryAdapter;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Event;
use League\Flysystem\Config;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;

beforeEach(function () {
    assertCloudinaryLiveCredentialsOrSkip($this);

    Event::fake();

    $cloudinary = new Cloudinary([
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'url' => [
            'secure' => true,
        ],
    ]);

    $this->adapter = new FlysystemCloudinaryAdapter($cloudinary);
});

it('can write', function () {
    $publicId = 'file-write-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();

    $this->adapter->write($publicId, $fakeImage, new Config);

    assertUploadResponse($this, $this->adapter->meta, $publicId);
    $this->assertSame($this->adapter->meta, $this->adapter->lastUploadMetadata());
    $this->adapter->delete($publicId); // cleanup
});

it('can write stream', function () {
    $publicId = 'file-write-stream-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();

    $this->adapter->writeStream($publicId, $fakeImage, new Config);

    assertUploadResponse($this, $this->adapter->meta, $publicId);
    $this->assertSame($this->adapter->meta, $this->adapter->lastUploadMetadata());
    $this->adapter->delete($publicId); // cleanup
});

it('can update', function () {
    $publicId = 'file-update-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();

    $meta = $this->adapter->update($publicId, $fakeImage, new Config);

    assertUploadResponse($this, $meta, $publicId);
    $this->adapter->delete($publicId); // cleanup
});

it('can update stream', function () {
    $publicId = 'file-update-stream-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();

    $meta = $this->adapter->updateStream($publicId, $fakeImage, new Config);

    assertUploadResponse($this, $meta, $publicId);
    $this->adapter->delete($publicId); // cleanup
});

function assertUploadResponse(mixed $test, array $meta, string $publicId): void
{
    $test->assertIsString($meta['contents']);
    $test->assertIsString($meta['etag']);
    $test->assertContains($meta['mimetype'], ['image/jpeg', 'image/jpg']);
    $test->assertSame($publicId, $meta['path']);
    $test->assertGreaterThan(0, $meta['size']);
    $test->assertIsInt($meta['timestamp']);
    $test->assertSame('file', $meta['type']);
    $test->assertIsInt($meta['version']);
    $test->assertIsString($meta['versionid']);
    $test->assertSame('public', $meta['visibility']);
}

it('can rename', function () {
    $path = 'file-old-path-'.rand();
    $newPath = 'file-new-path-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($path, $fakeImage, new Config);

    $bool = $this->adapter->rename($path, $newPath);

    $this->assertTrue($bool);
    $this->adapter->delete($newPath); // cleanup
});

it('does not rename if file is not found', function () {
    $path = 'file-does-not-exist';
    $newPath = 'file-renamed';

    $bool = $this->adapter->rename($path, $newPath);

    $this->assertFalse($bool);
});

it('does not rename if new path already exists', function () {
    $path = 'file-rename-'.rand();
    $newPath = 'file-already-exists-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($path, $fakeImage, new Config);
    $this->adapter->write($newPath, $fakeImage, new Config);

    $bool = $this->adapter->rename($path, $newPath);

    $this->assertFalse($bool);
    $this->adapter->delete($path); // cleanup
    $this->adapter->delete($newPath); // cleanup
});

it('can copy', function () {
    $path = 'file-old-copy-'.rand();
    $newPath = 'file-new-copy-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($path, $fakeImage, new Config);

    $this->adapter->copy($path, $newPath, new Config);

    $this->assertTrue($this->adapter->fileExists($newPath));
    $this->adapter->delete($path); // cleanup
    $this->adapter->delete($newPath); // cleanup
});

it('does not copy if file is not found', function () {
    $path = 'file-does-not-exist';
    $newPath = 'file-copied';

    $this->expectException(UnableToCopyFile::class);
    $this->adapter->copy($path, $newPath, new Config);
});

it('can delete', function () {
    $publicId = 'file-delete-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $this->adapter->delete($publicId);

    $this->assertFalse($this->adapter->fileExists($publicId));
});

it('can delete a directory', function () {
    $publicId = 'delete_dir/file-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $bool = $this->adapter->deleteDir('delete_dir');

    $this->assertTrue($bool);
});

it('can create a directory', function () {
    $directory = 'directory-'.rand();

    $meta = $this->adapter->createDir($directory, new Config);

    $this->assertSame([
        'path' => $directory,
        'type' => 'dir',
    ], $meta);
    $this->adapter->deleteDir($directory); // cleanup
});

it('can check if file exists', function () {
    $publicId = 'file-has-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $bool = $this->adapter->has($publicId);

    $this->assertTrue($bool);
    $this->adapter->delete($publicId); // cleanup
});

it('can check if file does not exist', function () {
    $publicId = 'file-does-not-exist';

    $bool = $this->adapter->has($publicId);

    $this->assertFalse($bool);
});

it('can read', function () {
    $publicId = 'file-read-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $content = $this->adapter->read($publicId);

    $this->assertSame($content, $fakeImage);
    $this->adapter->delete($publicId); // cleanup
});

it('can read stream', function () {
    $publicId = 'file-read-stream-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $stream = $this->adapter->readStream($publicId);

    $this->assertIsResource($stream);
    $this->adapter->delete($publicId); // cleanup
});

it('does not read if file is not found', function () {
    $publicId = 'file-does-not-exist';

    $this->expectException(UnableToReadFile::class);
    $this->adapter->read($publicId);
});

it('does not read stream if file is not found', function () {
    $publicId = 'file-does-not-exist';

    $this->expectException(UnableToReadFile::class);
    $this->adapter->readStream($publicId);
});

it('can list directory contents', function () {
    $files = iterator_to_array($this->adapter->listContents('sandbox', false));

    $this->assertIsArray($files);
});

it('does get size', function () {
    $publicId = 'file-get-size-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $size = $this->adapter->getSize($publicId);

    $this->assertGreaterThan(0, $size);
    $this->adapter->delete($publicId); // cleanup
});

it('does not get size if file is not found', function () {
    $publicId = 'file-does-not-exist';

    $this->expectException(UnableToRetrieveMetadata::class);
    $this->adapter->getSize($publicId);
});

it('does get mimetype', function () {
    $publicId = 'file-get-mimetype-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $mimeType = $this->adapter->getMimetype($publicId);

    $this->assertContains($mimeType, ['image/jpeg', 'image/jpg']);
    $this->adapter->delete($publicId); // cleanup
});

it('does not get mimetype if file is not found', function () {
    $publicId = 'file-does-not-exist';

    $this->expectException(UnableToRetrieveMetadata::class);
    $this->adapter->getMimetype($publicId);
});

it('does get timestamp', function () {
    $publicId = 'file-get-mimetype-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $timeStamp = $this->adapter->getTimestamp($publicId);

    $this->assertTrue((int) $timeStamp > 0);
    $this->adapter->delete($publicId); // cleanup
});

it('does not get timestamp if file is not found', function () {
    $publicId = 'file-does-not-exist';

    $this->expectException(UnableToRetrieveMetadata::class);
    $this->adapter->getTimestamp($publicId);
});

it('does get visibility', function () {
    $publicId = 'file-get-mimetype-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $visibility = $this->adapter->getVisibility($publicId);

    $this->assertEquals('public', $visibility);
    $this->adapter->delete($publicId); // cleanup
});

it('does not get visibility if file is not found', function () {
    $publicId = 'file-does-not-exist';

    $this->expectException(UnableToRetrieveMetadata::class);
    $this->adapter->getVisibility($publicId);
});

it('does get url', function () {
    $publicId = 'file-get-url-'.rand();
    $fakeImage = File::image('black.jpg')->getContent();
    $this->adapter->write($publicId, $fakeImage, new Config);

    $url = $this->adapter->getUrl($publicId);

    $this->assertStringStartsWith('https://', $url);
    $this->assertStringContainsString($publicId, $url);
    $this->adapter->delete($publicId); // cleanup
});

it('can move file', function () {
    $sourceId = 'source-file-'.rand();
    $source = File::image('black.jpg')->getContent();
    $movedToId = 'moved-file-'.rand();

    $this->assertFalse($this->adapter->fileExists($movedToId));

    $this->adapter->write($sourceId, $source, new Config);
    $this->adapter->move($sourceId, $movedToId, new Config);

    $this->assertFalse($this->adapter->fileExists($sourceId));
    $this->assertTrue($this->adapter->fileExists($movedToId));
});

it('cant move unexisted file', function () {
    $sourceId = 'source-file-'.rand();
    $movedToId = 'moved-file-'.rand();

    $this->assertFalse($this->adapter->fileExists($sourceId));
    $this->expectException(UnableToMoveFile::class);
    $this->adapter->move($sourceId, $movedToId, new Config);
});

it('can create and delete directory', function () {
    $directory = 'directory_to_create';
    $this->adapter->createDirectory($directory, new Config);

    $this->assertTrue($this->adapter->directoryExists($directory));

    $this->adapter->deleteDirectory($directory);
    $this->assertFalse($this->adapter->directoryExists($directory));
});

it('fileExists returns false for a missing path', function () {
    expect($this->adapter->fileExists('no-such-file-'.rand().'/x'))->toBeFalse();
});

it('directoryExists returns false for a path that was never created', function () {
    expect($this->adapter->directoryExists('no-such-dir-'.rand()))->toBeFalse();
});

it('has returns false for a missing file', function () {
    expect($this->adapter->has('missing-'.rand()))->toBeFalse();
});

it('rename returns false when source is missing', function () {
    expect($this->adapter->rename('missing-'.rand(), 'dest'))->toBeFalse();
});
