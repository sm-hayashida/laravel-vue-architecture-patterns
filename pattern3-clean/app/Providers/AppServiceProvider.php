<?php

namespace App\Providers;

use App\Infrastructure\Repositories\EloquentProductRepository;
use App\InterfaceAdapters\Presenters\ProductPresenter;
use App\UseCases\Products\AdjustStockInteractor;
use App\UseCases\Products\CreateProductInteractor;
use App\UseCases\Products\DecreaseStockInteractor;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;
use App\UseCases\Products\IncreaseStockInteractor;
use App\UseCases\Products\ListProductsInteractor;
use App\UseCases\Products\Ports\Input\AdjustStockInputPort;
use App\UseCases\Products\Ports\Input\CreateProductInputPort;
use App\UseCases\Products\Ports\Input\DecreaseStockInputPort;
use App\UseCases\Products\Ports\Input\IncreaseStockInputPort;
use App\UseCases\Products\Ports\Input\ListProductsInputPort;
use App\UseCases\Products\Ports\Output\ProductListOutputPort;
use App\UseCases\Products\Ports\Output\ProductOutputPort;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(CreateProductInputPort::class, CreateProductInteractor::class);
        $this->app->bind(IncreaseStockInputPort::class, IncreaseStockInteractor::class);
        $this->app->bind(DecreaseStockInputPort::class, DecreaseStockInteractor::class);
        $this->app->bind(AdjustStockInputPort::class, AdjustStockInteractor::class);
        $this->app->bind(ListProductsInputPort::class, ListProductsInteractor::class);

        $this->app->scoped(ProductPresenter::class);
        $this->app->scoped(ProductOutputPort::class, fn ($app) => $app->make(ProductPresenter::class));
        $this->app->scoped(ProductListOutputPort::class, fn ($app) => $app->make(ProductPresenter::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
