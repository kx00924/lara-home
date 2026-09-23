<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->index()->after('password');
            $table->string('avatar')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->json('preferences')->nullable()->after('is_active');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon', 40)->default('home');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->json('tags')->nullable();
            $table->json('colors')->nullable();
            $table->decimal('price', 10, 2)->default(0); // 0 = free
            $table->string('currency', 3)->default('USD');
            $table->string('cover_image')->nullable();
            $table->string('designer')->default('Home Studio');
            $table->unsignedInteger('area_sqm')->default(0);
            $table->boolean('featured')->default(false)->index();
            $table->boolean('published')->default(true)->index();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('purchases')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('trending_score')->default(0)->index();
            $table->timestamps();
            $table->index(['category_id', 'room_type_id', 'published']);
        });

        Schema::create('design_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('angle', 60)->default('Overview');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending')->index(); // pending|paid|failed|refunded
            $table->string('provider', 20)->default('demo'); // demo|stripe
            $table->string('provider_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'design_id', 'status']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('design_images');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('categories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'avatar', 'is_active', 'preferences']);
        });
    }
};
