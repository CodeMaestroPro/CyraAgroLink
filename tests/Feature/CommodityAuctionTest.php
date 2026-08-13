<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuctionBid;
use App\Models\CommodityAuction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Commodity Auction System.
 */
class CommodityAuctionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_commodity_auction_system(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('auction.system'));

        $response->assertOk();
        $response->assertSee('Commodity Auction System');
        $response->assertDontSee('36. COMMODITY AUCTION SYSTEM');
        $response->assertDontSee('36. Commodity Auction System');
        $response->assertSee('Live Auctions', false);
        $response->assertSee('All Commodities', false);
        $response->assertSee('Maize (White)', false);
        $response->assertSee('Highest Bid', false);
        $response->assertSee('₦310,000 /Ton', false);
        $response->assertSee('GreenLands Ltd', false);
        $response->assertSee('Place Bid', false);
        $response->assertSee('Rice (Parboiled)', false);
        $response->assertSee('₦420,000 /Ton', false);
        $response->assertSee('Bright Farms', false);
        $response->assertSee('Auction History', false);
        $response->assertSee('Sorghum', false);
        $response->assertSee('Soybean', false);
        $response->assertSee('Cassava', false);
        $response->assertSee('Sesame', false);
        $response->assertSee('Completed', false);
        $response->assertSee('₦235,000', false);
        $response->assertSee('View All Auctions', false);
        $response->assertSee('Ends in:', false);
        $response->assertSee('My Bids', false);
    }

    public function test_guest_cannot_view_commodity_auction_system(): void
    {
        $this->get(route('auction.system'))->assertRedirect(route('login'));
    }

    public function test_user_can_filter_and_place_bid_with_wallet_hold(): void
    {
        $user = User::factory()->create(['name' => 'Ada Farmer']);
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('auction.system'))->assertOk();

        $this->actingAs($user)
            ->get(route('auction.system', ['commodity' => 'Maize']))
            ->assertOk()
            ->assertSee('Maize (White)', false)
            ->assertDontSee('Rice (Parboiled)', false);

        $auction = CommodityAuction::query()->where('name', 'Maize (White)')->firstOrFail();
        $amount = $auction->nextMinBid();

        $this->actingAs($user)
            ->post(route('auction.bids.store'), [
                'auction_id' => $auction->id,
                'amount_ngn' => $amount,
                'commodity' => 'Maize',
            ])
            ->assertRedirect(route('auction.system', ['commodity' => 'Maize']).'#live');

        $bid = AuctionBid::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('leading', $bid->status);
        $this->assertSame($amount, $bid->amount_ngn);

        $auction->refresh();
        $this->assertSame($user->id, $auction->highest_bidder_id);
        $this->assertSame($amount, $auction->current_bid_ngn);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'auction_hold',
            'reference_id' => $bid->id,
            'amount' => $amount,
        ]);

        $this->assertSame(5_000_000 - $amount, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));

        $this->actingAs($user)
            ->get(route('auction.system', ['commodity' => 'Maize']))
            ->assertOk()
            ->assertSee('You are leading this auction', false)
            ->assertSee($bid->reference, false);
    }

    public function test_outbid_refunds_previous_bidder(): void
    {
        $first = User::factory()->create(['name' => 'First Bidder']);
        $second = User::factory()->create(['name' => 'Second Bidder']);

        Wallet::query()->create(['user_id' => $first->id, 'balance' => 5_000_000, 'currency' => 'NGN']);
        Wallet::query()->create(['user_id' => $second->id, 'balance' => 5_000_000, 'currency' => 'NGN']);

        $this->actingAs($first)->get(route('auction.system'))->assertOk();
        $auction = CommodityAuction::query()->where('name', 'Cassava (Fresh)')->firstOrFail();

        $firstAmount = $auction->nextMinBid();
        $this->actingAs($first)->post(route('auction.bids.store'), [
            'auction_id' => $auction->id,
            'amount_ngn' => $firstAmount,
        ]);

        $firstBid = AuctionBid::query()->where('user_id', $first->id)->firstOrFail();
        $balanceAfterFirst = (int) Wallet::query()->where('user_id', $first->id)->value('balance');
        $this->assertSame(5_000_000 - $firstAmount, $balanceAfterFirst);

        $secondAmount = $firstAmount + $auction->fresh()->min_increment_ngn;
        $this->actingAs($second)->post(route('auction.bids.store'), [
            'auction_id' => $auction->id,
            'amount_ngn' => $secondAmount,
        ]);

        $this->assertSame('outbid', $firstBid->fresh()->status);
        $this->assertSame('leading', AuctionBid::query()->where('user_id', $second->id)->value('status'));

        $this->assertSame(
            5_000_000,
            (int) Wallet::query()->where('user_id', $first->id)->value('balance')
        );

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $first->id,
            'category' => 'auction_refund',
            'reference_id' => $firstBid->id,
            'amount' => $firstAmount,
        ]);
    }

    public function test_user_cannot_bid_without_wallet_funds(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('auction.system'))->assertOk();
        $auction = CommodityAuction::query()->where('status', 'live')->firstOrFail();

        $response = $this->actingAs($user)->post(route('auction.bids.store'), [
            'auction_id' => $auction->id,
            'amount_ngn' => $auction->nextMinBid(),
        ]);

        $response->assertRedirect();
        $this->followRedirects($response)->assertSee('Insufficient', false);
        $this->assertSame(0, AuctionBid::query()->where('user_id', $user->id)->count());
    }

    public function test_expired_auctions_settle_to_winner(): void
    {
        $user = User::factory()->create(['name' => 'Winner']);
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('auction.system'))->assertOk();
        $auction = CommodityAuction::query()->where('name', 'Maize (White)')->firstOrFail();

        $this->actingAs($user)->post(route('auction.bids.store'), [
            'auction_id' => $auction->id,
            'amount_ngn' => $auction->nextMinBid(),
        ]);

        $auction->forceFill(['ends_at' => now()->subMinute()])->save();

        $this->actingAs($user)->get(route('auction.system'))->assertOk();

        $auction->refresh();
        $this->assertSame('ended', $auction->status);
        $this->assertSame($user->id, $auction->winner_id);
        $this->assertSame('won', AuctionBid::query()->where('user_id', $user->id)->value('status'));
        $this->assertSeeHistoryHas($user);
    }

    protected function assertSeeHistoryHas(User $user): void
    {
        $this->actingAs($user)
            ->get(route('auction.system'))
            ->assertOk()
            ->assertSee('Maize (White)', false)
            ->assertSee('Completed', false);
    }
}
