<?php

use App\Support\LocalImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pixel size of each gallery image, so only genuine 2:1 panoramas are
     * offered in the 360° tour. Existing local images are measured now.
     */
    public function up(): void
    {
        Schema::table('design_images', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable()->after('url');
            $table->unsignedInteger('height')->nullable()->after('width');
        });

        DB::table('design_images')->select('id', 'url')->orderBy('id')->each(function (object $image) {
            if ($size = LocalImage::size($image->url)) {
                DB::table('design_images')->where('id', $image->id)->update(['width' => $size[0], 'height' => $size[1]]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('design_images', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });
    }
};
