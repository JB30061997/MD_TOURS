<?php

namespace App\Providers;

use App\Services\ManagerActivityNotificationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Events\Audited;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
                ? true
                : null;
        });

        Event::listen(Audited::class, function (Audited $event) {
            app(ManagerActivityNotificationService::class)->notifyAudited(
                $event->model,
                $event->audit
            );
        });
    }
}
