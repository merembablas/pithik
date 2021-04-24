<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\IndodaxService;
use App\Services\BinanceService;
use App\Services\CoinMarketCapService;
use App\Services\CoinMarketCalService;
use App\Services\FixerService;
use App\Services\IndicatorService;

class ExchangeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(IndodaxService::class, function ($app) {
            return new IndodaxService();
        });

        $this->app->bind(BinanceService::class, function ($app) {
            return new BinanceService();
        });

        $this->app->bind(CoinMarketCapService::class, function ($app) {
            return new CoinMarketCapService();
        });

        $this->app->bind(FixerService::class, function ($app) {
            return new FixerService();
        });

        $this->app->bind(IndicatorService::class, function ($app) {
            return new IndicatorService();
        });

        $this->app->bind(CoinMarketCalService::class, function ($app) {
            return new CoinMarketCalService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
