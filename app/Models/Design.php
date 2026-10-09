<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Design extends Model
{
    protected $fillable = [
        'title', 'slug', 'summary', 'description', 'category_id', 'room_type_id', 'tags', 'colors',
        'price', 'currency', 'cover_image', 'designer', 'area_sqm', 'floors', 'featured', 'published',
        'views', 'purchases', 'likes', 'trending_score',
    ];

    protected $casts = [
        'tags' => 'array',
        'colors' => 'array',
        'floors' => 'array',
        'price' => 'float',
        'featured' => 'boolean',
        'published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Design $d) {
            if (blank($d->slug) || $d->isDirty('title')) {
                $d->slug = unique_slug($d, $d->title, 'design');
            }
            $d->trending_score = $d->computeTrending();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DesignImage::class)->orderBy('sort_order');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->price <= 0;
    }

    public function getCoverAttribute(): ?string
    {
        return $this->cover_image ?: ($this->images->first(fn (DesignImage $i) => ! $i->isViewablePanorama()) ?? $this->images->first())?->url;
    }

    /**
     * Floor names in order; floor number N is index N - 1.
     *
     * @return array<int, string>
     */
    public function floorNames(): array
    {
        $names = array_values(array_filter(array_map('trim', $this->floors ?? []), fn (string $n) => $n !== ''));

        return $names ?: ['Ground floor'];
    }

    /**
     * Images marked as 360° that are not 2:1, and are therefore left out of the tour.
     *
     * @return Collection<int, DesignImage>
     */
    public function misshapenPanoramas()
    {
        return $this->images->filter(fn (DesignImage $i) => $i->isPanorama() && ! $i->isViewablePanorama())->values();
    }

    /**
     * The 360° tour: one entry per floor that has panoramas, each with its scenes.
     * Only images that are genuinely 2:1 are included.
     * Scene URLs are only included when the visitor may see the full design.
     *
     * @return array<int, array{floor: int, name: string, scenes: array<int, array{id: int, title: string, description: ?string, url: ?string, yaw: float, pitch: float, fov: float}>}>
     */
    public function tour(bool $unlocked): array
    {
        $names = $this->floorNames();

        return $this->images
            ->filter(fn (DesignImage $i) => $i->isViewablePanorama())
            ->groupBy(fn (DesignImage $i) => max(1, (int) $i->floor))
            ->sortKeys()
            ->map(fn ($scenes, int $floor) => [
                'floor' => $floor,
                'name' => $names[$floor - 1] ?? 'Floor '.$floor,
                'scenes' => $scenes->sortBy('sort_order')->values()->map(fn (DesignImage $s, int $n) => [
                    'id' => $s->id,
                    // Bare numbers read like floor numbers, so they become "Room 1", "Room 2".
                    'title' => ctype_digit(trim((string) $s->title)) ? 'Room '.trim($s->title) : (trim((string) $s->title) ?: 'Room '.($n + 1)),
                    'description' => $s->description,
                    'url' => $unlocked ? $s->url : null,
                    'yaw' => $s->pano_yaw,
                    'pitch' => $s->pano_pitch,
                    'fov' => $s->pano_fov,
                ])->all(),
            ])
            ->values()
            ->all();
    }

    public function computeTrending(): int
    {
        $ageDays = $this->created_at ? $this->created_at->diffInDays(now()) : 0;
        $freshness = max(0, 30 - $ageDays);

        return (int) round($this->views + $this->purchases * 25 + $this->likes * 5 + $freshness * 2 + ($this->featured ? 40 : 0));
    }

    /* ---------- Scopes ---------- */

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    public function scopeTrending(Builder $q): Builder
    {
        return $q->orderByDesc('trending_score')->orderByDesc('views');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            foreach (preg_split('/\s+/', $term) as $word) {
                $like = '%'.$word.'%';
                $w->orWhere('title', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('designer', 'like', $like)
                    ->orWhere('tags', 'like', $like);
            }
        });
    }

    /** Whether the given user (or guest) may see every image. */
    public function unlockedFor(?User $user): bool
    {
        if ($this->is_free) {
            return true;
        }
        if (! $user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->owns($this);
    }

    /** Designs similar to this one: same room + style first, then shared tags, then trending. */
    public function similar(int $limit = 6)
    {
        $tags = $this->tags ?? [];
        $query = static::published()->where('id', '!=', $this->id)->with(['category', 'roomType']);
        $score = '(CASE WHEN room_type_id = ? THEN 50 ELSE 0 END) + (CASE WHEN category_id = ? THEN 40 ELSE 0 END)';
        $bindings = [$this->room_type_id, $this->category_id];
        foreach (array_slice($tags, 0, 6) as $tag) {
            $score .= ' + (CASE WHEN tags LIKE ? THEN 8 ELSE 0 END)';
            $bindings[] = '%"'.$tag.'"%';
        }
        $score .= ' + MIN(trending_score / 200.0, 20)';

        return $query->selectRaw('designs.*, ('.$score.') as sim_score', $bindings)
            ->orderByDesc('sim_score')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
