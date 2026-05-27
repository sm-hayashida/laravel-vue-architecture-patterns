<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Repositories\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
    }
}
