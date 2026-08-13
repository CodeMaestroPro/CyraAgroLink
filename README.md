# CyraAgroLink

Enterprise agricultural commerce, investment, and digital ecosystem platform developed by **CYRA-TECH LTD**.

## Stack

- Laravel 12 / PHP 8.2+
- MySQL
- Tailwind CSS 4 / Vite
- Blade templates
- Laravel Sanctum (API tokens)
- Laravel Breeze (web authentication)

## Architecture

```
app/
├── Contracts/          # Interfaces (Repositories, Services)
├── Enums/              # Backed enums (roles, statuses)
├── Exceptions/         # Domain exceptions
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/     # Versioned REST controllers
│   │   └── Web/        # Blade web controllers
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Providers/
├── Repositories/Eloquent/
├── Services/           # Business logic
└── Support/            # Shared helpers (ApiResponse, etc.)
```

**Rules**

- Controllers stay thin
- Services own business logic
- Repositories own data access
- Form Requests own validation
- Policies own authorization
- API Resources own response shaping

## Quick start (XAMPP)

1. Create MySQL database `cyra_agrolink`
2. Copy `.env.example` values and set DB credentials
3. Install and boot:

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Or via Apache: point virtual host / open `http://localhost/Cyra-Agro/public`

## Default seed accounts

| Email | Password | Role |
|-------|----------|------|
| admin@cyraagrolink.com | Password@123 | admin |
| farmer@cyraagrolink.com | Password@123 | farmer |
| investor@cyraagrolink.com | Password@123 | investor |

## API (v1)

Base URL: `/api/v1`

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/health` | No | Health check |
| POST | `/auth/register` | No | Register + token |
| POST | `/auth/login` | No | Login + token |
| POST | `/auth/logout` | Sanctum | Revoke token |
| GET | `/profile` | Sanctum | Current user |

See [docs/API.md](docs/API.md) and [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Development

```bash
composer run dev          # server + queue + logs + vite
php artisan test          # run test suite
vendor/bin/pint           # PSR-12 formatting
```

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [API](docs/API.md)
- [Database](docs/DATABASE.md)
- [Environment](docs/ENVIRONMENT.md)
- [Deployment](docs/DEPLOYMENT.md)
- [Market readiness](docs/MARKET_READINESS.md) — phased roadmap, budget buckets, keep/defer/partner matrix
