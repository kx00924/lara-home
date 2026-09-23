<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/designs', [DesignController::class, 'index'])->name('designs.index');
Route::get('/designs/suggest', [DesignController::class, 'suggest'])->name('designs.suggest');
Route::get('/designs/{design:slug}', [DesignController::class, 'show'])->name('designs.show');
Route::get('/designs/{design:slug}/download', [DesignController::class, 'download'])->name('designs.download');
Route::post('/designs/{design:slug}/like', [DesignController::class, 'like'])->name('designs.like');
Route::post('/newsletter', fn () => back()->with('success', 'Thank you. You are on the list.'))->name('newsletter');

Route::get('/theme/{theme}', function (string $theme) {
    abort_unless($theme === 'site' || in_array($theme, available_themes(), true), 404);
    $theme === 'site' ? session()->forget('theme') : session(['theme' => $theme]);

    return redirect()->to(url()->previous() ?: route('home'));
})->name('theme');

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, SetLocale::LOCALES), 404);
    session(['locale' => $locale]);

    return back();
})->name('lang');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/designs/{design:slug}/checkout', [CheckoutController::class, 'start'])->name('checkout.start');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{order}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{order}/pay', [CheckoutController::class, 'demoPay'])->name('checkout.pay');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::post('/designs/recompute-trending', [Admin\DesignController::class, 'recomputeTrending'])->name('designs.recompute');
    Route::patch('/designs/{design}/toggle', [Admin\DesignController::class, 'toggle'])->name('designs.toggle');
    Route::resource('designs', Admin\DesignController::class)->except('show');
    Route::resource('categories', Admin\CategoryController::class)->except(['show', 'create', 'edit']);
    Route::resource('room-types', Admin\RoomTypeController::class)->except(['show', 'create', 'edit'])->parameters(['room-types' => 'roomType']);
    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/themes', [Admin\ThemeController::class, 'index'])->name('themes.index');
    Route::post('/themes/{theme}/activate', [Admin\ThemeController::class, 'activate'])->name('themes.activate');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/upload', Admin\UploadController::class)->name('upload');
});
