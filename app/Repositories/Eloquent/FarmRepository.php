<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\FarmRepositoryInterface;
use App\Enums\FarmStatus;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent farm repository.
 */
class FarmRepository extends BaseRepository implements FarmRepositoryInterface
{
    public function __construct(Farm $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     *
     * @return Collection<int, Farm>
     */
    public function getForUser(User $user): Collection
    {
        /** @var Collection<int, Farm> $farms */
        $farms = $this->model->newQuery()
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $farms;
    }

    /**
     * {@inheritdoc}
     */
    public function findDraftForUser(User $user): ?Farm
    {
        /** @var Farm|null $farm */
        $farm = $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('status', FarmStatus::Draft)
            ->latest()
            ->first();

        return $farm;
    }

    /**
     * {@inheritdoc}
     */
    public function findOwnedOrFail(User $user, int $farmId): Farm
    {
        /** @var Farm $farm */
        $farm = $this->model->newQuery()
            ->where('user_id', $user->id)
            ->findOrFail($farmId);

        return $farm;
    }
}
