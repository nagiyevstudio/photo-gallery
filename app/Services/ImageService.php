<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageService
{
    protected ImageManager $manager;

    public function __construct()
    {
        // Select driver based on server capabilities (prefer Imagick, fallback to GD)
        if (extension_loaded('imagick')) {
            $this->manager = new ImageManager(new ImagickDriver);
        } else {
            $this->manager = new ImageManager(new GdDriver);
        }
    }

    /**
     * Process and save web-optimized version of the image.
     */
    public function createWebVersion(string $sourcePath, string $targetPath, int $maxSide = 2000, int $quality = 85): array
    {
        try {
            $image = $this->manager->decodePath($sourcePath);
            $width = $image->width();
            $height = $image->height();

            // Resize if exceeds max side
            if ($width > $height) {
                if ($width > $maxSide) {
                    $image->scale(width: $maxSide);
                }
            } else {
                if ($height > $maxSide) {
                    $image->scale(height: $maxSide);
                }
            }

            // Save as WebP
            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality);
            $encoded->save($targetPath);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => filesize($targetPath),
            ];
        } catch (\Throwable $e) {
            Log::error('ImageService createWebVersion failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Process and save thumbnail version of the image.
     */
    public function createThumbnail(string $sourcePath, string $targetPath, int $height = 400, int $quality = 80): void
    {
        try {
            $image = $this->manager->decodePath($sourcePath);

            // Resize height to target, maintaining aspect ratio
            $image->scale(height: $height);

            // Save as WebP
            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality);
            $encoded->save($targetPath);
        } catch (\Throwable $e) {
            Log::error('ImageService createThumbnail failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get image dimensions.
     */
    public function getDimensions(string $path): array
    {
        try {
            $image = $this->manager->decodePath($path);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Throwable $e) {
            Log::error('ImageService getDimensions failed: '.$e->getMessage());

            return ['width' => 0, 'height' => 0];
        }
    }
}
