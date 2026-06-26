<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class Photo extends Model
{
    protected $fillable = [
        'gallery_id',
        'original_filename',
        'original_path',
        'web_path',
        'thumbnail_path',
        'width',
        'height',
        'file_size',
        'sort_order',
        'is_processed',
    ];

    protected $casts = [
        'is_processed' => 'boolean',
    ];

    protected $appends = [
        'web_url',
        'thumbnail_url',
        'aspect_ratio',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function getWebUrlAttribute(): ?string
    {
        return $this->web_path ? asset('storage/' . $this->web_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null;
    }

    public function getAspectRatioAttribute(): float
    {
        if ($this->width && $this->height) {
            return round($this->width / $this->height, 4);
        }
        return 1.0;
    }

    /**
     * Generate signed URL for single photo download
     */
    public function getDownloadUrlAttribute(): string
    {
        return URL::temporarySignedRoute(
            'photo.download',
            now()->addHours(24),
            ['photo' => $this->id]
        );
    }
}
