<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent implementation of the base repository contract.
 *
 * @template TModel of Model
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @param  TModel  $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->newQuery()
            ->with($relations)
            ->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = []
    ): LengthAwarePaginator {
        return $this->model->newQuery()
            ->with($relations)
            ->paginate($perPage, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int|string $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->newQuery()
            ->with($relations)
            ->find($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int|string $id, array $columns = ['*'], array $relations = []): Model
    {
        $record = $this->findById($id, $columns, $relations);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel($this->model::class, [$id]);
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $attributes, array $relations = []): ?Model
    {
        return $this->model->newQuery()
            ->with($relations)
            ->where($attributes)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->refresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);

        return (bool) $record->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function restore(int|string $id): bool
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($this->model::class), true)) {
            return false;
        }

        /** @var Model&SoftDeletes $record */
        $record = $this->model->newQuery()->withTrashed()->findOrFail($id);

        return (bool) $record->restore();
    }
}
