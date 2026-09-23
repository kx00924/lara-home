<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Models\Category;
use App\Models\RoomType;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        // Shared with every view: site settings + navigation taxonomy.
        View::composer('*', function ($view) {
            static $shared = null;
            if ($shared === null) {
                $shared = ['site' => Setting::DEFAULTS, 'navCategories' => collect(), 'navRooms' => collect(), 'locales' => SetLocale::LOCALES];
                if (Schema::hasTable('settings')) {
                    $shared['site'] = Setting::allValues();
                    $shared['navCategories'] = Category::active()->withCount(['designs' => fn ($q) => $q->where('published', true)])->get();
                    $shared['navRooms'] = RoomType::active()->withCount(['designs' => fn ($q) => $q->where('published', true)])->get();
                }
            }
            $view->with($shared);
        });
    }
}
