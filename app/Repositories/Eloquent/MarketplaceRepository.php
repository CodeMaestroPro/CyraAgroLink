<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\MarketplaceRepositoryInterface;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCommodity;
use App\Models\MarketplaceSupplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent marketplace repository.
 */
class MarketplaceRepository implements MarketplaceRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getActiveCategories(): Collection
    {
        return MarketplaceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getFeaturedCommodities(int $limit = 8): Collection
    {
        return $this->filterCommodities(null, null, null, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function filterCommodities(?string $query = null, ?string $categorySlug = null, ?string $state = null, int $limit = 24): Collection
    {
        $builder = MarketplaceCommodity::query()
            ->with(['category', 'owner'])
            ->where('status', 'active');

        $this->applyFilters($builder, $query, $categorySlug, $state);

        return $builder
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getForUser(User $user, int $limit = 50): Collection
    {
        return MarketplaceCommodity::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getTopSuppliers(int $limit = 8): Collection
    {
        return MarketplaceSupplier::query()
            ->where('status', 'active')
            ->where('is_top', true)
            ->orderByDesc('rating')
            ->orderByDesc('review_count')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSuppliers(int $limit = 24): Collection
    {
        return MarketplaceSupplier::query()
            ->where('status', 'active')
            ->orderByDesc('is_top')
            ->orderByDesc('rating')
            ->orderByDesc('review_count')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function searchCommodities(string $query, int $limit = 24): Collection
    {
        return $this->filterCommodities($query, null, null, $limit);
    }

    /**
     * @param  Builder<MarketplaceCommodity>  $builder
     */
    protected function applyFilters(Builder $builder, ?string $query, ?string $categorySlug, ?string $state): void
    {
        $term = trim((string) $query);
        $category = trim((string) $categorySlug);
        $stateFilter = trim((string) $state);

        if ($category !== '') {
            $builder->whereHas('category', function (Builder $q) use ($category): void {
                $q->where('slug', $category);
            });
        }

        if ($stateFilter !== '') {
            $builder->where('state', $stateFilter);
        }

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $builder->where(function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('state', 'like', $like)
                    ->orWhere('scientific_name', 'like', $like)
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($like): void {
                        $categoryQuery
                            ->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
            });
        }
    }
}
