<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'is_password_protected',
        'password',
        'allow_download',
        'expires_at',
        'status',
        'hero_photo_id',
        'total_views',
        'total_downloads',
    ];

    protected $casts = [
        'is_password_protected' => 'boolean',
        'allow_download' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // --- Relationships ---

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class)->orderBy('sort_order');
    }

    public function projectViews(): HasMany
    {
        return $this->hasMany(ProjectView::class);
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function heroPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'hero_photo_id');
    }

    // --- Helper Methods ---

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        if ($this->heroPhoto) {
            return asset('storage/' . $this->heroPhoto->web_path);
        }

        // Fallback to the first photo of the first gallery
        $firstGallery = $this->galleries->first();
        if ($firstGallery) {
            $firstPhoto = $firstGallery->photos->first();
            if ($firstPhoto) {
                return asset('storage/' . $firstPhoto->web_path);
            }
        }

        return null;
    }

    public function totalPhotosSize(): int
    {
        return (int) $this->galleries->flatMap->photos->sum('file_size');
    }

    public function totalPhotosCount(): int
    {
        return (int) $this->galleries->flatMap->photos->count();
    }

    public function zipFilePath(): string
    {
        return storage_path("app/zips/{$this->id}.zip");
    }

    public function getZipInfo(): ?array
    {
        $path = $this->zipFilePath();
        if (!file_exists($path)) {
            return null;
        }

        $zip = new \ZipArchive();
        $filesCount = 0;
        if ($zip->open($path) === true) {
            $filesCount = $zip->numFiles;
            $zip->close();
        }

        $projectPhotosCount = $this->totalPhotosCount();
        $isOutdated = ($filesCount !== $projectPhotosCount);
        $fileSize = filesize($path);

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $fileSize > 0 ? (int) floor(log($fileSize, 1024)) : 0;
        $formattedSize = round($fileSize / pow(1024, max($i, 0)), 2) . ' ' . ($units[$i] ?? 'MB');

        return [
            'exists' => true,
            'size' => $fileSize,
            'formatted_size' => $formattedSize,
            'modified_at' => filemtime($path),
            'formatted_date' => date('d.m.Y H:i', filemtime($path)),
            'files_count' => $filesCount,
            'project_photos_count' => $projectPhotosCount,
            'is_outdated' => $isOutdated,
        ];
    }

    public function formattedTotalPhotosSize(): string
    {
        $bytes = $this->totalPhotosSize();
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now())->where('status', 'active');
    }
}
