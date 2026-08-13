<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\CropRepositoryInterface;
use App\Contracts\Repositories\FarmRepositoryInterface;
use App\Contracts\Repositories\MarketplaceRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Repositories\Eloquent\CropRepository;
use App\Repositories\Eloquent\FarmRepository;
use App\Repositories\Eloquent\MarketplaceRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

/**
 * Bind repository and service contracts to concrete implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository and service bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        UserServiceInterface::class => UserService::class,
        FarmRepositoryInterface::class => FarmRepository::class,
        CropRepositoryInterface::class => CropRepository::class,
        MarketplaceRepositoryInterface::class => MarketplaceRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
