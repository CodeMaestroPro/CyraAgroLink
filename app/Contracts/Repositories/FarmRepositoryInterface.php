<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Farm repository contract.
 */
interface FarmRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve farms owned by a user.
     *
     * @return Collection<int, Farm>
     */
    public function getForUser(User $user): Collection;

    /**
     * Find a draft registration for the user, if any.
     */
    public function findDraftForUser(User $user): ?Farm;

    /**
     * Find a farm owned by the given user or fail.
     */
    public function findOwnedOrFail(User $user, int $farmId): Farm;
}
