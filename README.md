# 📞 Phone Assigner API

A lightweight Laravel 12 API that records and assigns phone numbers to campaigns.
Authentication is powered by Laravel Sanctum using the default users table.

## 🚀 Features

- Laravel 12 + Sanctum token authentication
- Default users table for login & token management
- Protected /assign endpoint for recording phone numbers
- MySQL schema with columns:
  - caller_id (varchar 10)
  - area_code (varchar 10)
  - active (boolean)
  - last_assigned_date (datetime)
  - last_assigned_campaign (varchar 20)
  - user_id (FK → users.id)

## 📦 Installation

```bash
git clone <your-repo-url>
cd phone-assigner-api
composer install
cp .env.example .env
php artisan key:generate
```

Set up your database in .env, then run:

```bash
php artisan migrate
php artisan db:seed --class=UserSeeder   # optional demo user
```

## 🔑 Authentication

This project uses Laravel Sanctum personal access tokens. Tokens are scoped with abilities (e.g. assign:phone).

1) Login to get a token

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "demo@example.com",
    "password": "password",
    "abilities": ["assign:phone"]
  }'
```

Response:

```json
{
  "token": "plain-text-token",
  "abilities": ["assign:phone"],
  "user_id": 1
}
```

2) Use the token in requests

Add header:

```
Authorization: Bearer <token>
```

3) Logout / revoke current token

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer <token>"
```

## 📡 API Endpoints

### POST /api/login

- Body: `{ "email": string, "password": string, "abilities": string[] }`
- Returns a new Sanctum token.

### POST | GET /api/assign

- Protected (auth:sanctum, requires assign:phone ability).
- Body/query params:
  - phone (required, string)
  - campaign_id (required, string)
  - area_code (optional, string)
  - caller_id (optional, string)
- Saves or updates the phone in the phone_pool table.

Example:

```bash
curl -X POST http://localhost:8000/api/assign \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{ "phone": "809-555-1234", "campaign_id": "CMP-2025-09" }'
```

Response:

```json
{
  "message": "Phone recorded & assigned.",
  "data": {
    "id": 1,
    "caller_id": "8095551234",
    "area_code": "809",
    "active": true,
    "last_assigned_date": "2025-09-25T10:00:00Z",
    "last_assigned_campaign": "CMP-2025-09",
    "user_id": 1
  }
}
```

### GET /api/phone/{callerId}

- Protected
- Lookup a phone record by caller_id.

## 🗄️ Database Schema

```mermaid
erDiagram
  users ||--o{ phone_pool : "owns"

  users {
    bigint id PK
    string name
    string email
    string password
    timestamps
  }

  phone_pool {
    bigint id PK
    string caller_id (10)
    string area_code (10)
    boolean active
    datetime last_assigned_date
    string last_assigned_campaign (20)
    bigint user_id FK
    timestamps
  }
```

## 🧪 Testing

We recommend Pest. Example:

```bash
php artisan test
```

## ⚙️ Configuration

- CORS: config/cors.php allows api/*
- Token Expiration: set in .env

```env
SANCTUM_EXPIRATION=43200 # minutes (30 days)
```

## 📝 License

MIT – feel free to use and adapt.
