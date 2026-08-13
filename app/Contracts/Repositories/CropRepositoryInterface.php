<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Crop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Crop repository contract.
 */
interface CropRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, Crop>
     */
    public function getForUser(User $user): Collection;

    /**
     * Find a crop owned by the user or fail.
     */
    public function findOwnedOrFail(User $user, int $cropId): Crop;

    /**
     * Latest active crop for the user (for default landing).
     */
    public function findLatestActiveForUser(User $user): ?Crop;
}
