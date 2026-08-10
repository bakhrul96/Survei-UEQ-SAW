<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->forcePublicUrlWhenBehindProxy();
    }

    /**
     * Force generated absolute URLs to the public HTTPS root when the app sits
     * behind a reverse proxy or tunnel (ngrok, Cloudflare, load balancer).
     * Enabled by setting FORCE_PUBLIC_URL=true in the environment.
     */
    protected function forcePublicUrlWhenBehindProxy(): void
    {
        if (! config('app.force_public_url', false)) {
            return;
        }

        $root = rtrim((string) config('app.url'), '/');
        if ($root === '') {
            return;
        }

        URL::forceRootUrl($root);
        if (str_starts_with(strtolower($root), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
