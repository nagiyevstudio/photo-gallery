<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'photo_id',
        'type',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }
}
