<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CropRepositoryInterface;
use App\Models\Crop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent crop repository.
 */
class CropRepository extends BaseRepository implements CropRepositoryInterface
{
    public function __construct(Crop $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     *
     * @return Collection<int, Crop>
     */
    public function getForUser(User $user): Collection
    {
        /** @var Collection<int, Crop> $crops */
        $crops = $this->model->newQuery()
            ->with('farm')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $crops;
    }

    /**
     * {@inheritdoc}
     */
    public function findOwnedOrFail(User $user, int $cropId): Crop
    {
        /** @var Crop $crop */
        $crop = $this->model->newQuery()
            ->with('farm')
            ->where('user_id', $user->id)
            ->findOrFail($cropId);

        return $crop;
    }

    /**
     * {@inheritdoc}
     */
    public function findLatestActiveForUser(User $user): ?Crop
    {
        /** @var Crop|null $crop */
        $crop = $this->model->newQuery()
            ->with('farm')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        return $crop;
    }
}
