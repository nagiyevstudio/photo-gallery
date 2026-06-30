<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageTest extends TestCase
{
    public function test_public_disk_does_not_depend_on_an_apache_symlink(): void
    {
        $this->assertSame(
            public_path('storage/gallery-test.webp'),
            Storage::disk('public')->path('gallery-test.webp'),
        );
    }
}
