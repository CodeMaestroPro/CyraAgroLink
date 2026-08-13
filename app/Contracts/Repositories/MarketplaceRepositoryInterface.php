<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Marketplace data-access contract.
 */
interface MarketplaceRepositoryInterface
{
    /**
     * Active categories ordered for navigation.
     *
     * @return Collection<int, \App\Models\MarketplaceCategory>
     */
    public function getActiveCategories(): Collection;

    /**
     * Featured commodity listings.
     *
     * @return Collection<int, \App\Models\MarketplaceCommodity>
     */
    public function getFeaturedCommodities(int $limit = 8): Collection;

    /**
     * Filter active commodities by optional category slug, state, and search.
     *
     * @return Collection<int, \App\Models\MarketplaceCommodity>
     */
    public function filterCommodities(?string $query = null, ?string $categorySlug = null, ?string $state = null, int $limit = 24): Collection;

    /**
     * Commodities owned by a user.
     *
     * @return Collection<int, \App\Models\MarketplaceCommodity>
     */
    public function getForUser(User $user, int $limit = 50): Collection;

    /**
     * Top-rated suppliers.
     *
     * @return Collection<int, \App\Models\MarketplaceSupplier>
     */
    public function getTopSuppliers(int $limit = 8): Collection;

    /**
     * All active suppliers.
     *
     * @return Collection<int, \App\Models\MarketplaceSupplier>
     */
    public function getActiveSuppliers(int $limit = 24): Collection;

    /**
     * Search commodities by name, city, or state.
     *
     * @return Collection<int, \App\Models\MarketplaceCommodity>
     */
    public function searchCommodities(string $query, int $limit = 24): Collection;
}
