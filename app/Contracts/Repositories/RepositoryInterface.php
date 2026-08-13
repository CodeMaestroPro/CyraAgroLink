<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository contract for data-access abstractions.
 */
interface RepositoryInterface
{
    /**
     * Retrieve all records matching optional criteria.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $relations
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Paginate records.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $relations
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = []
    ): LengthAwarePaginator;

    /**
     * Find a record by primary key.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $relations
     */
    public function findById(int|string $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find a record by primary key or fail.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $relations
     */
    public function findOrFail(int|string $id, array $columns = ['*'], array $relations = []): Model;

    /**
     * Find the first record matching attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $relations
     */
    public function findBy(array $attributes, array $relations = []): ?Model;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Soft-delete or hard-delete a record.
     */
    public function delete(int|string $id): bool;

    /**
     * Restore a soft-deleted record when supported.
     */
    public function restore(int|string $id): bool;
}
