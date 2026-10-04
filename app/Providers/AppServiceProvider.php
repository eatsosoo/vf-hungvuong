<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleVariant;
use App\Observers\AuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->useLangPath(resource_path('lang'));
    }

    public function boot(): void
    {
        DevCommands::artisan('schedule:work --no-interaction', 'scheduler');
        Gate::define('manage-content', fn (User $user): bool => $user->is_active &&
            in_array($user->role, [UserRole::Admin, UserRole::Manager, UserRole::Editor], true));
        Gate::define('manage-catalog', fn (User $user): bool => $user->is_active &&
            in_array($user->role, [UserRole::Admin, UserRole::Manager], true));
        Gate::define('manage-users', fn (User $user): bool => $user->is_active && $user->role === UserRole::Admin);
        Gate::define('manage-settings', fn (User $user): bool => $user->is_active && $user->role === UserRole::Admin);
        Gate::define('view-reports', fn (User $user): bool => $user->is_active &&
            in_array($user->role, [UserRole::Admin, UserRole::Manager], true));
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(hash('sha256',
                mb_strtolower((string) $request->input('email')).'|'.$request->ip())),
            Limit::perMinute(10)->by('email/'.hash('sha256', mb_strtolower((string) $request->input('email')))),
            Limit::perMinute(20)->by('ip/'.$request->ip()),
            Limit::perHour(100)->by('hour/'.$request->ip()),
        ]);
        RateLimiter::for('leads', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        foreach ([User::class, Post::class, Vehicle::class, VehicleVariant::class, VehicleColor::class,
            Category::class, Tag::class, Promotion::class, Page::class, Lead::class, LeadNote::class,
            Setting::class, Media::class] as $model) {
            $model::observe(AuditObserver::class);
        }
        View::composer([
            'site.*', 'welcome', 'layouts.site', 'components.site.*',
            'components.vehicle-card', 'components.post-card',
        ], function ($view): void {
            $view->with('clientRoutePrefix', app()->getLocale() === 'en' ? 'en.' : '');
        });
        View::composer(['layouts.site', 'welcome'], function ($view): void {
            $view->with('siteSettings', Setting::values());
            $view->with('testDriveVehicles', Vehicle::query()->active()->with(['media', 'variants'])
                ->orderBy('name')->get());
        });
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
