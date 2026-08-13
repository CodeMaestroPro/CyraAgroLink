# Database

## Engine

Primary: **MySQL** (XAMPP / production)  
Local fallback: SQLite (testing)

## Core tables (scaffold)

### `users`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| phone | string(30) unique nullable | |
| email_verified_at | timestamp nullable | |
| password | string | hashed |
| role | string(32) indexed | default `farmer` |
| status | string(32) indexed | default `active` |
| remember_token | string | |
| timestamps | | |
| deleted_at | soft delete | |

### Supporting

- `password_reset_tokens`
- `sessions`
- `cache` / `cache_locks`
- `jobs` / `job_batches` / `failed_jobs`
- `personal_access_tokens` (Sanctum)

## Standards for future migrations

1. Use foreign keys with explicit `constrained()` / `cascadeOnDelete()` where appropriate
2. Index foreign keys and filter columns
3. Soft deletes on business entities
4. Avoid denormalized duplication unless justified with a documented cache strategy
5. Prefer enums in PHP (`App\Enums`) over magic strings

## Commands

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
```
