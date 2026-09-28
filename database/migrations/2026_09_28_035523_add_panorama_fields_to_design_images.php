<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gallery images can be 360° panoramas assigned to a floor with a starting
     * view; designs carry the names of their floors.
     */
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->json('floors')->nullable()->after('area_sqm');
        });

        Schema::table('design_images', function (Blueprint $table) {
            $table->string('kind', 20)->default('photo')->after('url');
            $table->unsignedTinyInteger('floor')->nullable()->after('kind');
            $table->float('pano_yaw')->default(0)->after('floor');
            $table->float('pano_pitch')->default(0)->after('pano_yaw');
            $table->float('pano_fov')->default(75)->after('pano_pitch');
        });
    }

    public function down(): void
    {
        Schema::table('design_images', function (Blueprint $table) {
            $table->dropColumn(['kind', 'floor', 'pano_yaw', 'pano_pitch', 'pano_fov']);
        });

        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn('floors');
        });
    }
};
