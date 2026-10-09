<?php

use App\Models\Category;
use App\Models\Design;
use App\Models\RoomType;
use Illuminate\Database\Migrations\Migration;

/**
 * Titles without Latin letters used to produce an empty slug, which breaks the
 * design's URL and its admin edit page. Give every such row a usable slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([Design::class, Category::class, RoomType::class] as $model) {
            $model::query()->whereNull('slug')->orWhere('slug', '')->get()->each(function ($row) {
                $row->slug = '';
                $row->saveQuietly();
                $row->slug = unique_slug($row, $row->title ?? $row->name, strtolower(class_basename($row)));
                $row->saveQuietly();
            });
        }
    }

    public function down(): void {}
};
