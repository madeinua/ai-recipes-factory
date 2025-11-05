# AI Recipes Factory (Laravel 12, MySQL, Redis, Docker)

Queue-driven micro-backend that accepts a list of ingredients, generates a recipe asynchronously (AI or Fake), stores it, and returns results via polling or webhook.

- **Framework:** Laravel 12 (PHP 8.3)
- **Infra:** MySQL 8, Redis 7, Docker Compose
- **Queue:** Laravel queues (default + `webhooks`)
- **Security posture:** internal service behind TLS / network controls; **no users / no Sanctum**
- **Tests:** Feature tests; AI mocked to **FakeAiRecipeGenerator** in tests

---

## ✨ Features

- **Async API**: `POST /recipes/generate` → returns `requestId` (202). Poll or receive a **webhook** when done.
- **Deduplication**: canonicalizes + hashes ingredient CSV; avoids duplicate processing; instant completion for already computed inputs.
- **Replay-safe enqueue**: short critical section guarded by cache lock.
- **Rate limiting**: global API + stricter per-endpoint limits.
- **Domain-first design** (DDD-ish): entities, value objects, repositories, services separated from infrastructure.
- **Swappable AI provider**: `AiRecipeGenerator` interface → Fake or OpenAI-backed implementation.
- **No user model / auth**: designed as an internal microservice.

---

## 🧭 API

### 1) Generate Recipe (async)

**POST** `/api/v1/recipes/generate`

**Body**

```json
{
    "ingredients": "tomato, basil, pasta",
    "webhook_url": "https://example.test/hooks/recipes"
}
```

**Responses**

- **202 Accepted (new)**:

```json
{
    "requestId": "uuid",
    "status": "PENDING",
    "deduped": false,
    "location": "https://host/api/v1/recipes/requests/{id}"
}
```

- **202 Accepted (deduped + already completed)**:

```json
{
    "requestId": "uuid",
    "status": "COMPLETED",
    "deduped": true,
    "location": "https://host/api/v1/recipes/requests/{id}"
}
```

- **202 Accepted (deduped + already pending somewhere else)**:

```json
{
    "requestId": "uuid",
    "status": "PENDING",
    "deduped": true,
    "location": "https://host/api/v1/recipes/requests/{id}"
}
```

**Rate limits**

- Global: `60/min` per IP
- Generate: `10/min` and `100/hour` per IP

---

### 2) Poll Request

**GET** `/api/v1/recipes/requests/{id}`

**Pending**

```json
{
    "id": "uuid",
    "status": "PENDING",
    "errorMessage": null
}
```

**Completed**

```json
{
    "id": "uuid",
    "status": "COMPLETED",
    "recipe": {
        "id": "uuid",
        "title": "Tomato Basil Pasta",
        "excerpt": "Auto-generated demo recipe.",
        "instructions": [
            "..."
        ],
        "numberOfPersons": 2,
        "timeToCook": 15,
        "timeToPrepare": 8,
        "ingredients": [
            {
                "name": "tomato",
                "value": 100,
                "measure": "mg"
            }
        ],
        "createdAt": "2025-10-26T12:00:00+00:00",
        "updatedAt": "2025-10-26T12:00:00+00:00"
    }
}
```

**Failed**

```json
{
    "id": "uuid",
    "status": "FAILED",
    "errorMessage": "AI provider down"
}
```

---

### 3) Read Recipe

**GET** `/api/v1/recipes/{id}`

**200**

```json
{
    "id": "uuid",
    "title": "Tomato Basil Pasta",
    "excerpt": "Auto-generated demo recipe.",
    "instructions": [
        "..."
    ],
    "numberOfPersons": 2,
    "timeToCook": 15,
    "timeToPrepare": 8,
    "ingredients": [
        {
            "name": "tomato",
            "value": 100,
            "measure": "mg"
        }
    ],
    "createdAt": "...",
    "updatedAt": "..."
}
```

**404**

```json
{
    "message": "Not found"
}
```

---

## 🔔 Webhooks

If `webhook_url` is provided in the generate request, the service will POST:

```json
{
    "request_id": "uuid",
    "status": "COMPLETED",
    "recipe_id": "uuid or null",
    "timestamp": "2025-10-26T12:00:00Z"
}
```

- Webhook delivery is handled by `NotifyWebhookJob` on the **`webhooks` queue** (retries, backoff).
- For demo purposes, a simple HTTP 2xx from your endpoint is treated as success.

---

## Running locally

### Prerequisites

- **Docker** and **Docker Compose** installed.
- Copy `.env.example` to `.env` and adjust settings if needed.

```bash
sail up -d
```

---

## Testing

The suite uses **PHPUnit** with Laravel testing utilities.

**Run tests**

Recommended:

```bash
npm run test
```

Alternatively, directly via Artisan or PHPUnit:

```bash
php artisan test
```

or

```bash
vendor/bin/phpunit
```

---

## Troubleshooting

- **`This cache store does not support tagging/locks`**  
  Set `CACHE_STORE=redis` and ensure Redis is running and reachable.
- **Jobs never run**  
  Start a worker: `php artisan queue:work --queue=default,webhooks`.
- **429 Too Many Requests**  
  You hit throttles; slow down or raise limits in `AppServiceProvider`.
- **Webhook timeouts**  
  Target must accept POST JSON; service retries 3 times with 30s backoff and logs failures.
