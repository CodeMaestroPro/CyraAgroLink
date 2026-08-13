# Architecture

CyraAgroLink follows a modular, layered Laravel architecture suitable for large-scale African agricultural commerce.

## Layers

| Layer | Responsibility | Location |
|-------|----------------|----------|
| Controllers | HTTP orchestration only | `app/Http/Controllers` |
| Form Requests | Input validation & authorization gates | `app/Http/Requests` |
| Resources | API response transformation | `app/Http/Resources` |
| Policies | Model authorization | `app/Policies` |
| Services | Business rules & orchestration | `app/Services` |
| Repositories | Persistence / query logic | `app/Repositories/Eloquent` |
| Contracts | Interfaces for DI | `app/Contracts` |
| Models | Eloquent domain entities | `app/Models` |
| Enums | Typed constants | `app/Enums` |
| Jobs / Events / Notifications | Async & side effects | `app/Jobs`, `app/Events`, `app/Notifications` |

## Request flow

```
HTTP Request
  → Middleware (auth, role, throttle, ForceJsonResponse)
  → Form Request (validation)
  → Controller (thin)
  → Service (business logic)
  → Repository (data access)
  → Model / Database
  → Resource / ApiResponse
  → HTTP Response
```

## Dependency injection

Bindings are registered in `App\Providers\RepositoryServiceProvider`:

- `UserRepositoryInterface` → `UserRepository`
- `UserServiceInterface` → `UserService`

Always type-hint contracts in controllers and services.

## API responses

All API endpoints use `App\Support\ApiResponse`:

```json
{
  "success": true,
  "message": "Success",
  "data": {},
  "meta": {}
}
```

Errors:

```json
{
  "success": false,
  "message": "Validation failed.",
  "error_code": "VALIDATION_ERROR",
  "data": null,
  "errors": {}
}
```

## Roles

Defined in `App\Enums\UserRole`:

- `admin`
- `farmer`
- `investor`
- `buyer`
- `supplier`
- `agent`

Use middleware: `middleware('role:admin,agent')`

## Conventions

- PSR-12 + `declare(strict_types=1);`
- Constructor injection
- Soft deletes on domain entities where applicable
- Foreign keys + indexes on every migration
- No business logic in controllers
- Prefer queued jobs for slow side effects
