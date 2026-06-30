<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDirectory = sys_get_temp_dir().'/photo-gallery-image-service-'.bin2hex(random_bytes(8));
        mkdir($this->tempDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDirectory.'/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tempDirectory);

        parent::tearDown();
    }

    public function test_it_creates_web_and_thumbnail_versions_with_intervention_v4(): void
    {
        $sourcePath = $this->tempDirectory.'/source.png';
        $webPath = $this->tempDirectory.'/web.webp';
        $thumbnailPath = $this->tempDirectory.'/thumbnail.webp';

        copy(public_path('logo.png'), $sourcePath);

        $service = new ImageService;
        $details = $service->createWebVersion($sourcePath, $webPath, maxSide: 100, quality: 85);
        $service->createThumbnail($sourcePath, $thumbnailPath, height: 40, quality: 80);

        $this->assertLessThanOrEqual(100, $details['width']);
        $this->assertLessThanOrEqual(100, $details['height']);
        $this->assertGreaterThan(0, $details['size']);
        $this->assertSame('image/webp', mime_content_type($webPath));
        $this->assertSame('image/webp', mime_content_type($thumbnailPath));
        $this->assertSame(['width' => 257, 'height' => 257], $service->getDimensions($sourcePath));
    }
}
