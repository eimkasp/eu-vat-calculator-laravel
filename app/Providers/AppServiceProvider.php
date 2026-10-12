<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Mcp\VatMcpServer;
use Filament\Facades\Filament;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Cloudflare Rocket Loader would otherwise defer these until after load, delaying the calculator and the theme.
        Vite::useScriptTagAttributes(['data-cfasync' => 'false']);
        Livewire::useScriptTagAttributes(['data-cfasync' => 'false']);

        Gate::define('viewPulse', fn (User $user): bool => $user->canAccessPanel(Filament::getPanel('admin')));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('vies', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(VatMcpServer::REQUESTS_PER_MINUTE)->by($request->ip()));
    }
}
