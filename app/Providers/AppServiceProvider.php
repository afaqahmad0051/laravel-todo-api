<?php

namespace App\Providers;

use App\Repositories\TodoRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\TodoRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TodoRepositoryInterface::class, TodoRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
