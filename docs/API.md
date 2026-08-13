# API Documentation (v1)

Base path: `/api/v1`  
Auth: Laravel Sanctum Bearer tokens  
Content-Type: `application/json`

## Health

### `GET /health`

Returns service health metadata.

**Response `200`**

```json
{
  "success": true,
  "message": "CyraAgroLink API is healthy.",
  "data": {
    "status": "ok",
    "service": "CyraAgroLink",
    "version": "v1",
    "timestamp": "2026-07-29T00:00:00+00:00"
  }
}
```

## Authentication

### `POST /auth/register`

Rate limited: `auth` (10/min/IP)

**Body**

| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| email | required, email, unique |
| phone | optional, unique |
| password | required, confirmed, password rules |
| password_confirmation | required with password |
| role | optional: farmer, investor, buyer, supplier, agent |

**Response `201`**

```json
{
  "success": true,
  "message": "Registration successful.",
  "data": {
    "user": { "id": 1, "name": "...", "email": "...", "role": "farmer" },
    "token": "1|....",
    "token_type": "Bearer"
  }
}
```

### `POST /auth/login`

**Body:** `email`, `password`

**Response `200`:** user + token

### `POST /auth/logout`

**Headers:** `Authorization: Bearer {token}`

Revokes the current access token.

## Profile

### `GET /profile`

**Headers:** `Authorization: Bearer {token}`

Returns the authenticated user resource.

## Error envelope

```json
{
  "success": false,
  "message": "Validation failed.",
  "error_code": "VALIDATION_ERROR",
  "data": null,
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

## Status codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthenticated |
| 403 | Forbidden / inactive account |
| 422 | Validation / business rule failure |
| 429 | Rate limited |
| 500 | Server error |
