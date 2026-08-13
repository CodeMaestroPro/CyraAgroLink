<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademyCourse;
use App\Models\ArbitrageOpportunity;
use App\Models\CommodityAuction;
use App\Models\ConsumerProduct;
use App\Models\EquipmentListing;
use App\Models\FuturesContract;
use App\Models\InsurancePlan;
use App\Models\InvestmentOpportunity;
use App\Models\MarketplaceCommodity;
use App\Models\ProcessingFactory;
use App\Models\User;
use App\Services\Academy\LearningAcademyService;
use App\Services\Arbitrage\ArbitrageService;
use App\Services\Auction\CommodityAuctionService;
use App\Services\Consumer\ConsumerMarketplaceService;
use App\Services\Equipment\EquipmentMarketplaceService;
use App\Services\Futures\CommodityFuturesService;
use App\Services\Insurance\FarmInsuranceService;
use App\Services\Investment\InvestmentMarketplaceService;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Processing\FoodProcessingNetworkService;
use App\Support\DemoSeeding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ensures request-time demo catalogs cannot populate production databases.
 */
class DemoSeedingGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeding_defaults_off_in_production_environment(): void
    {
        config(['cyra.allow_demo_seeding' => null]);
        app()->detectEnvironment(fn () => 'production');

        $this->assertFalse(DemoSeeding::allowed());
    }

    public function test_explicit_override_can_disable_seeding_outside_production(): void
    {
        config(['cyra.allow_demo_seeding' => false]);

        $this->assertFalse(DemoSeeding::allowed());
    }

    public function test_disabled_seeding_does_not_insert_demo_catalog_rows(): void
    {
        config(['cyra.allow_demo_seeding' => false]);
        $user = User::factory()->create();

        app(MarketplaceService::class)->getCatalog();
        app(MarketplaceService::class)->getFeaturedForHome();
        app(InvestmentMarketplaceService::class)->ensureCatalog();
        app(LearningAcademyService::class)->ensureCatalog();
        app(ConsumerMarketplaceService::class)->getStorefrontData($user);
        app(CommodityAuctionService::class)->getAuctionData($user);
        app(EquipmentMarketplaceService::class)->getMarketplaceData($user);
        app(FoodProcessingNetworkService::class)->getNetworkData($user);
        app(FarmInsuranceService::class)->getPlatformData($user);

        try {
            app(CommodityFuturesService::class)->getBoardData($user);
        } catch (\App\Exceptions\BusinessLogicException) {
            // Expected when contracts are not seeded.
        }

        $this->assertSame(0, MarketplaceCommodity::query()->count());
        $this->assertSame(0, InvestmentOpportunity::query()->count());
        $this->assertSame(0, AcademyCourse::query()->count());
        $this->assertSame(0, ConsumerProduct::query()->count());
        $this->assertSame(0, CommodityAuction::query()->count());
        $this->assertSame(0, EquipmentListing::query()->count());
        $this->assertSame(0, ProcessingFactory::query()->count());
        $this->assertSame(0, InsurancePlan::query()->count());
        $this->assertSame(0, FuturesContract::query()->count());
        $this->assertSame(0, ArbitrageOpportunity::query()->count());
    }

    public function test_arbitrage_without_seed_reports_empty_not_fake_opportunity(): void
    {
        config(['cyra.allow_demo_seeding' => false]);

        $this->expectException(\App\Exceptions\BusinessLogicException::class);
        $this->expectExceptionMessage('No active arbitrage opportunities');

        app(ArbitrageService::class)->getDashboard();
    }
}
