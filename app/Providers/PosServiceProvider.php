<?php

namespace App\Providers;

use App\Contracts\PosRateProviderInterface;
use App\Contracts\PosSelectionStrategyInterface;
use App\Services\LowestCostSelectionStrategy;
use App\Services\ApiPosRateProvider;
use Illuminate\Support\ServiceProvider;

class PosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PosRateProviderInterface::class,
            fn () => new ApiPosRateProvider(
                apiUrl: config('pos.api_url'),
            ),
        );

        $this->app->singleton(
            PosSelectionStrategyInterface::class,
            LowestCostSelectionStrategy::class,
        );
    }
}
