# Environment Variables

Copy `.env.example` to `.env` and configure:

## Application

| Variable | Description | Example |
|----------|-------------|---------|
| APP_NAME | Display name | CyraAgroLink |
| APP_ENV | Environment | local / staging / production |
| APP_DEBUG | Debug mode | false in production |
| APP_URL | Public URL | http://localhost |
| APP_KEY | Encryption key | `php artisan key:generate` |

## Cyra platform

| Variable | Description | Default |
|----------|-------------|---------|
| CYRA_APP_NAME | Platform name | CyraAgroLink |
| CYRA_COMPANY | Company name | CYRA-TECH LTD |
| CYRA_API_VERSION | API version label | v1 |
| CYRA_API_PREFIX | API route prefix | api/v1 |
| CYRA_DEFAULT_PER_PAGE | Pagination default | 15 |
| CYRA_MAX_PER_PAGE | Pagination max | 100 |
| CYRA_DEFAULT_ROLE | Default registration role | farmer |

## Database (MySQL / XAMPP)

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cyra_agrolink
DB_USERNAME=root
DB_PASSWORD=
```

## Mail / Queue / Cache

| Variable | Recommended local | Recommended production |
|----------|-------------------|------------------------|
| QUEUE_CONNECTION | database | redis |
| CACHE_STORE | database | redis |
| SESSION_DRIVER | database | redis |
| MAIL_MAILER | log | smtp / ses |

## Sanctum

Configure stateful domains in `config/sanctum.php` when using SPA cookie auth.
Token auth works out of the box for mobile / API clients.
