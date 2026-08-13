<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Exceptions\BusinessLogicException;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit/integration tests for UserService.
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_with_default_farmer_role(): void
    {
        /** @var UserService $service */
        $service = $this->app->make(UserService::class);

        $user = $service->register([
            'name' => 'Kofi Mensah',
            'email' => 'kofi@example.com',
            'password' => 'Password@123',
        ]);

        $this->assertSame('kofi@example.com', $user->email);
        $this->assertSame(UserRole::Farmer, $user->role);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        /** @var UserService $service */
        $service = $this->app->make(UserService::class);

        $this->expectException(BusinessLogicException::class);

        $service->register([
            'name' => 'Duplicate',
            'email' => 'dup@example.com',
            'password' => 'Password@123',
        ]);
    }

    public function test_repository_binding_resolves(): void
    {
        $this->assertInstanceOf(
            UserRepositoryInterface::class,
            $this->app->make(UserRepositoryInterface::class)
        );
    }
}
