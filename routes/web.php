<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

foreach (['' => '', 'en' => 'en.'] as $prefix => $namePrefix) {
    Route::prefix($prefix)->name($namePrefix)
        ->group(function (): void {
            Route::get('/', [SiteController::class, 'home'])->name('home');
            Route::get('/xe', [SiteController::class, 'vehicles'])->name('vehicles.index');
            Route::get('/xe/{slug}', [SiteController::class, 'vehicle'])->name('vehicles.show');
            Route::get('/bai-viet', [SiteController::class, 'posts'])->name('posts.index');
            Route::get('/bai-viet/{slug}', [SiteController::class, 'post'])->name('posts.show');
            Route::get('/khuyen-mai', [SiteController::class, 'promotions'])->name('promotions.index');
            Route::get('/khuyen-mai/{slug}', [SiteController::class, 'promotion'])->name('promotions.show');
            Route::get('/trang/{slug}', [SiteController::class, 'page'])->name('pages.show');
            Route::get('/lien-he', [SiteController::class, 'contact'])->name('contact');
            Route::post('/yeu-cau', [LeadController::class, 'store'])
                ->middleware('throttle:leads')->name('leads.store');
        });
}
Route::get('/media/{media}', [SiteController::class, 'media'])->name('media.show');
Route::get('/sitemaps/{section}/{page}.xml', [SiteController::class, 'sitemapPage'])
    ->whereIn('section', ['static', 'posts', 'vehicles', 'promotions', 'pages'])
    ->whereNumber('page')->name('sitemap.page');
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::middleware('guest')->group(function (): void {
    Route::get('/dang-nhap', [AuthController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::post('/notifications/read',
        [Admin\DashboardController::class,
            'readNotifications'])->name('notifications.read');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/password', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');
    Route::post('/posts/bulk', [Admin\PostController::class, 'bulk'])->name('posts.bulk');
    Route::post('/post-editor/analyze', Admin\PostEditorController::class)
        ->middleware('throttle:90,1')->name('posts.analyze');
    Route::get('/posts/{post}/preview', [Admin\PostController::class, 'preview'])->name('posts.preview');
    Route::resource('posts', Admin\PostController::class)->except('show');
    Route::resource('vehicles', Admin\VehicleController::class)->except('show');
    Route::resource('promotions', Admin\PromotionController::class)->except('show');
    Route::resource('pages', Admin\PageController::class)->except('show');
    Route::resource('users', Admin\UserController::class)->except(['show', 'destroy']);
    Route::get('/taxonomies/{kind}', [Admin\TaxonomyController::class, 'index'])
        ->whereIn('kind', ['categories', 'tags'])->name('taxonomies.index');
    Route::post('/taxonomies/{kind}', [Admin\TaxonomyController::class, 'store'])
        ->whereIn('kind', ['categories', 'tags'])->name('taxonomies.store');
    Route::put('/taxonomies/{kind}/{taxonomy}', [Admin\TaxonomyController::class, 'update'])
        ->whereIn('kind', ['categories', 'tags'])->whereNumber('taxonomy')->name('taxonomies.update');
    Route::delete('/taxonomies/{kind}/{taxonomy}', [Admin\TaxonomyController::class, 'destroy'])
        ->whereIn('kind', ['categories', 'tags'])->whereNumber('taxonomy')->name('taxonomies.destroy');
    Route::get('/leads', [Admin\LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}/edit', [Admin\LeadController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [Admin\LeadController::class, 'update'])->name('leads.update');
    Route::get('/media', [Admin\MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [Admin\MediaController::class, 'store'])->name('media.store');
    Route::put('/media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export',
        [Admin\ReportController::class,
            'export'])->middleware('throttle:5,1')->name('reports.export');
    Route::get('/audit', [Admin\ReportController::class, 'audit'])->name('audit.index');
});
