<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * User domain service containing registration and profile business logic.
 */
class UserService implements UserServiceInterface
{
    /** @var list<string> */
    private const REGISTRABLE_ROLES = [
        UserRole::Farmer->value,
        UserRole::Investor->value,
        UserRole::Buyer->value,
        UserRole::Supplier->value,
        UserRole::Agent->value,
    ];

    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser(int $id): User
    {
        /** @var User $user */
        $user = $this->userRepository->findOrFail($id);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            if ($this->userRepository->findByEmail($data['email']) !== null) {
                throw new BusinessLogicException('A user with this email already exists.');
            }

            $requestedRole = isset($data['role']) ? (string) $data['role'] : UserRole::Farmer->value;
            $role = in_array($requestedRole, self::REGISTRABLE_ROLES, true)
                ? $requestedRole
                : UserRole::Farmer->value;

            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role' => $role,
                'status' => UserStatus::Active,
            ])->save();

            return $user->refresh();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function updateProfile(User $user, array $data): User
    {
        // Never allow privilege escalation via profile updates.
        unset($data['role'], $data['status'], $data['password'], $data['email_verified_at']);

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $existing = $this->userRepository->findByEmail($data['email']);

            if ($existing !== null && $existing->id !== $user->id) {
                throw new BusinessLogicException('A user with this email already exists.');
            }
        }

        /** @var User $updated */
        $updated = $this->userRepository->update($user->id, $data);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(User $user): bool
    {
        $user->forceFill([
            'status' => UserStatus::Inactive,
        ])->save();

        return $this->userRepository->delete($user->id);
    }
}
