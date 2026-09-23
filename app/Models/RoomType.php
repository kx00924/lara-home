<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RoomType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image', 'icon', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (RoomType $r) {
            if (blank($r->slug) || $r->isDirty('name')) {
                $r->slug = Str::slug($r->name);
            }
        });
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
