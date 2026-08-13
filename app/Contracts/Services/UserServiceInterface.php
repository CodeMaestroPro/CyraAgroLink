<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * User domain service contract.
 */
interface UserServiceInterface
{
    /**
     * Paginate users for administrative listings.
     */
    public function listUsers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Retrieve a single user by identifier.
     */
    public function getUser(int $id): User;

    /**
     * Register a new platform user.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User;

    /**
     * Update an existing user profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Soft-delete a user account.
     */
    public function deactivate(User $user): bool;
}
