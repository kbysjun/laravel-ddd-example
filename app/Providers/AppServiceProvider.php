<?php

namespace App\Providers;

use App\Domain\Repositories\PostRepositoryInterface;
use App\Infrastructure\Repositories\DatabasePostRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PostRepositoryInterface::class, DatabasePostRepository::class);

        if ($this->app->runningInConsole() && class_exists(CollisionServiceProvider::class)) {
            $this->app->register(CollisionServiceProvider::class);
        }
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
