<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent user repository.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findBy(['email' => $email]);

        return $user;
    }

    /**
     * {@inheritdoc}
     *
     * @return Collection<int, User>
     */
    public function getByRole(string $role): Collection
    {
        /** @var Collection<int, User> $users */
        $users = $this->model->newQuery()
            ->where('role', $role)
            ->get();

        return $users;
    }
}
