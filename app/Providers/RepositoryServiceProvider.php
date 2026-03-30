<?php

namespace App\Providers;

use App\Repositories\Contracts\PosRateRepositoryInterface;
use App\Repositories\Eloquent\PosRateRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PosRateRepositoryInterface::class,
            PosRateRepository::class,
        );
    }
}
