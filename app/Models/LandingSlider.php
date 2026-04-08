<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingSlider extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'title',
        'sub_title',
        'link',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getImageUrlAttribute()
    {
        $path = (string) ($this->image ?? '');
        if ($path === '') {
            return asset('images/placeholder.jpg');
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, 'images/')) {
            return asset($path);
        }

        // Serve via API endpoint to avoid /storage 403 on some hosts.
        return Storage::disk('public')->exists($path)
            ? url('api/v1/storage/' . ltrim($path, '/'))
            : asset('images/placeholder.jpg');
    }
}
