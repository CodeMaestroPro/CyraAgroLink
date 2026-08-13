<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_landing_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('CyraAgroLink', false)
            ->assertSee('Connecting Agriculture, Markets, Investment and Opportunities Across Africa.', false)
            ->assertSee('One Ecosystem. Endless Possibilities.', false)
            ->assertSee('Digital Farm Hub', false)
            ->assertSee('Get Started', false)
            ->assertSee('Explore Platform', false)
            ->assertSee('Featured agricultural products', false)
            ->assertSee('Browse marketplace', false)
            ->assertSee('Sign in to sell', false)
            ->assertSee('Maize', false)
            ->assertSee('Yam', false)
            ->assertSee('Sorghum', false)
            ->assertSee('Soybean', false)
            ->assertSee('About Us', false)
            ->assertSee('All rights reserved.', false)
            ->assertSee('aria-label="Toggle light and dark mode"', false)
            ->assertSee('Farmers', false)
            ->assertSee('Listings', false)
            ->assertSee('Maize Expansion', false)
            ->assertSee('Modern Maize Farming', false)
            ->assertDontSee('125K+', false);
    }

    public function test_home_page_shows_dashboard_uploaded_listings(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('marketplace.index'));

        $this->actingAs($user)->post(route('marketplace.store'), [
            'name' => 'Home Page Yam',
            'price_per_ton' => 210000,
            'city' => 'Makurdi',
            'state' => 'Benue',
            'is_featured' => '1',
            'image' => UploadedFile::fake()->image('yam.jpg'),
        ])->assertRedirect(route('marketplace.index', ['view' => 'listings']));

        $this->assertDatabaseHas('marketplace_commodities', [
            'name' => 'Home Page Yam',
            'user_id' => $user->id,
            'is_featured' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Home Page Yam', false)
            ->assertSee('Makurdi, Benue', false);
    }

    public function test_nav_section_anchors_are_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('id="marketplace"', false)
            ->assertSee('id="invest"', false)
            ->assertSee('id="logistics"', false)
            ->assertSee('id="resources"', false)
            ->assertSee('id="about"', false);
    }

    public function test_home_page_exposes_live_investment_and_fleet_data(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Fund farms. Earn from real harvests.', false)
            ->assertSee('Cassava Farm', false)
            ->assertSee('Move harvests across Africa with confidence', false)
            ->assertSee('Learn, grow, and lead with Cyra Academy', false)
            ->assertSee('Open Learning Academy', false);
    }

    public function test_home_page_fully_translates_for_french(): void
    {
        $response = $this->withSession([SetLocale::SESSION_KEY => 'fr'])
            ->get(route('home'));

        $response->assertOk()
            ->assertSee(__('home.hero.headline', [], 'fr'), false)
            ->assertSee(__('home.hero.cta_explore', [], 'fr'), false)
            ->assertSee(__('home.stats.farmers', [], 'fr'), false)
            ->assertSee(__('home.solutions.heading', [], 'fr'), false)
            ->assertSee(__('home.marketplace.heading', [], 'fr'), false)
            ->assertSee(__('home.marketplace.cta_browse', [], 'fr'), false)
            ->assertSee(__('home.marketplace.cta_sign_in_to_sell', [], 'fr'), false)
            ->assertSee(__('home.invest.heading', [], 'fr'), false)
            ->assertSee(__('opportunities.maize_expansion.title', [], 'fr'), false)
            ->assertSee(__('academy.modern-maize-farming.title', [], 'fr'), false)
            ->assertSee(__('home.logistics.heading', [], 'fr'), false)
            ->assertSee(__('home.about.kicker', [], 'fr'), false)
            ->assertSee('Tous droits réservés.', false)
            ->assertDontSee('Explore Platform', false)
            ->assertDontSee('Featured agricultural products', false);
    }

    public function test_home_page_translates_for_yoruba_and_igbo(): void
    {
        $this->withSession([SetLocale::SESSION_KEY => 'yo'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Ṣopọ̀ iṣẹ́ àgbẹ̀', false)
            ->assertSee('Àwọn àgbẹ̀', false)
            ->assertSee('Bẹ̀rẹ̀ nísinsinyí', false);

        $this->withSession([SetLocale::SESSION_KEY => 'ig'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Jikọọ ọrụ ugbo', false)
            ->assertSee('Ndị ọrụ ugbo', false)
            ->assertSee('Malite ugbu a', false);
    }
}
