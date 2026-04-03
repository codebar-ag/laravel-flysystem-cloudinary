<?php

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use CodebarAg\FlysystemCloudinary\CloudinaryPathNormalizer;
use CodebarAg\FlysystemCloudinary\CloudinaryResponseMapper;
use CodebarAg\FlysystemCloudinary\Concerns\InteractsWithCloudinaryMetadata;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use Mockery\MockInterface;

final class MetadataTestDouble
{
    use InteractsWithCloudinaryMetadata;

    public function __construct(
        private readonly Cloudinary $cloudinary,
        private readonly CloudinaryPathNormalizer $paths,
        private readonly CloudinaryResponseMapper $mapper,
    ) {}

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

    public function fetchMetadata(string $path, string $type): FileAttributes
    {
        return $this->getMetadata($path, $type);
    }
}

it('maps admin asset to file attributes', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->asset')
            ->once()
            ->andReturn(new ApiResponse([
                'public_id' => 'file',
                'bytes' => 50,
                'created_at' => '2022-02-02T02:02:02Z',
                'resource_type' => 'raw',
                'format' => 'txt',
            ], []));
    });

    $paths = new CloudinaryPathNormalizer(null);
    $double = new MetadataTestDouble($mock, $paths, new CloudinaryResponseMapper($paths));

    $fa = $double->fetchMetadata('file', FileAttributes::ATTRIBUTE_FILE_SIZE);

    expect($fa->path())->toBe('file')
        ->and($fa->fileSize())->toBe(50);
});

it('wraps admin asset failures in UnableToRetrieveMetadata', function () {
    $mock = $this->mock(Cloudinary::class, function (MockInterface $mock) {
        $mock->shouldReceive('adminApi->asset')
            ->once()
            ->andThrow(new Exception('not found'));
    });

    $paths = new CloudinaryPathNormalizer(null);
    $double = new MetadataTestDouble($mock, $paths, new CloudinaryResponseMapper($paths));

    $this->expectException(UnableToRetrieveMetadata::class);
    $double->fetchMetadata('missing', FileAttributes::ATTRIBUTE_FILE_SIZE);
});
