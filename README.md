# AI Recipes Factory (Laravel 12 + React, MySQL, Redis, Docker)

Queue-driven full-stack application that accepts a list of ingredients, generates recipes asynchronously using AI, and displays them in a beautiful React interface.

- **Backend:** Laravel 12 (PHP 8.3)
- **Frontend:** React 19 + TypeScript + Vite + Tailwind CSS v4
- **Infra:** MySQL 8, Redis 7, Docker Compose (Laravel Sail)
- **Queue:** Laravel queues (default + `webhooks`)
- **Security posture:** internal service behind TLS / network controls; **no users / no Sanctum**
- **Tests:** Feature tests; AI mocked to **FakeAiRecipeGenerator** in tests

---

## Features

### Backend
- **Async API**: `POST /recipes/generate` → returns `requestId` (202). Poll or receive a **webhook** when done.
- **Deduplication**: canonicalizes + hashes ingredient CSV; avoids duplicate processing; instant completion for already computed inputs.
- **Replay-safe enqueue**: short critical section guarded by cache lock.
- **Rate limiting**: global API + stricter per-endpoint limits.
- **Domain-first design** (DDD-ish): entities, value objects, repositories, services separated from infrastructure.
- **API Resources**: Clean transformation layer using Laravel Resources to control API responses.
- **Swappable AI provider**: `AiRecipeGenerator` interface → Fake or OpenAI-backed implementation.
- **No user model / auth**: designed as an internal microservice.

### Frontend
- **React 19 + TypeScript**: Modern, type-safe component architecture.
- **Beautiful UI**: Tailwind CSS v4 with gradient backgrounds and smooth animations.
- **Smart polling**: Automatic status updates with configurable intervals.
- **Example ingredients**: Quick-start buttons with pre-filled ingredient combinations.
- **Responsive design**: Works seamlessly on mobile and desktop.
- **Error handling**: Graceful error states with retry functionality.
- **Real-time feedback**: Loading states for PENDING → PROCESSING → COMPLETED.

---

## API

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
        ]
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
    "data": {
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
        ]
    }
}
```

**404**

```json
{
    "message": "Not found"
}
```

---

## Frontend Architecture

The frontend is built with **React 19**, **TypeScript**, and **Tailwind CSS v4**, providing a modern and type-safe user interface.

### Project Structure

```
resources/js/
├── components/
│   ├── App.tsx              # Main app with state management
│   ├── RecipeForm.tsx       # Ingredient input form
│   ├── RecipeDisplay.tsx    # Recipe display with ingredients & instructions
│   └── LoadingStatus.tsx    # Loading states and animations
├── services/
│   └── recipeApi.ts         # API client with polling logic
├── types/
│   └── recipe.ts            # TypeScript interfaces
└── app.tsx                  # App entry point
```

### Key Components

**RecipeForm**
- Ingredient input with validation (2-3000 characters)
- Pre-filled example ingredient combinations
- Real-time validation feedback

**RecipeDisplay**
- Beautiful card layout with title, excerpt, and metadata
- Visual indicators for servings, prep time, and cook time
- Organized ingredients list with checkmarks
- Step-by-step numbered instructions
- "New Recipe" button to start over

**LoadingStatus**
- Animated loading states for PENDING/PROCESSING
- Smooth transitions between states
- Visual feedback with spinners and bouncing dots

**API Service**
- `generateRecipe()` - Submit ingredients
- `pollForRecipe()` - Smart polling with callbacks
- Configurable polling interval (default: 2s) and max attempts (default: 60)

### State Management

Uses React's `useState` with a discriminated union type for clean state transitions:
```typescript
type AppState =
    | { type: 'form' }
    | { type: 'loading'; status: RecipeRequestStatus }
    | { type: 'recipe'; recipe: Recipe }
    | { type: 'error'; message: string };
```

---

## Webhooks

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

- **Docker** and **Docker Compose** installed
- **Node.js** (v18 or higher) and **npm**
- Copy `.env.example` to `.env` and adjust settings if needed

### Quick Start (Full Stack)

```bash
# Start Docker containers (MySQL, Redis, Queue Worker)
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail npm install

# Generate application key
./vendor/bin/sail artisan key:generate

# Run database migrations
./vendor/bin/sail artisan migrate

# Start Vite dev server (for development)
npm run dev

# OR build for production
npm run build
```

**Note:** To terminate the development server:
- **Windows**: Ctrl+C
- **macOS**: q+Enter

Your application will be available at:
- **Frontend (Vite)**: http://localhost:5173
- **Backend API**: http://localhost/api/v1
- **Mailpit UI**: http://localhost:8025

### Available npm Scripts

All npm scripts can be run directly from the host machine (no `./vendor/bin/sail` prefix needed).

**Docker Management:**
- `npm run up` - Start all containers in detached mode

**Development:**
- `npm run dev` - Start Vite development server with HMR
- `npm run build` - Build frontend for production

**Testing:**
- `npm run test` - Run backend tests (includes cache clear)
- `npm run test:fast` - Run backend tests without cache clear

### Alternative: Running Commands Inside Sail Container

If you prefer to run npm/node commands inside the Docker container:

```bash
# Start all services
./vendor/bin/sail up -d

# Install npm packages
./vendor/bin/sail npm install

# Start Vite dev server in container
./vendor/bin/sail npm run dev

# Run backend tests
./vendor/bin/sail artisan test
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

### Backend Issues

- **`This cache store does not support tagging/locks`**
  Set `CACHE_STORE=redis` and ensure Redis is running and reachable.
- **Jobs never run**
  Start a worker: `php artisan queue:work --queue=default,webhooks` or ensure the queue container is running.
- **429 Too Many Requests**
  You hit throttles; slow down or raise limits in `AppServiceProvider`.
- **Webhook timeouts**
  Target must accept POST JSON; service retries 3 times with 30s backoff and logs failures.

### Frontend Issues

- **Vite port already in use**
  Change `VITE_PORT` in `.env` (default: 5173).
- **API calls failing**
  Ensure the backend is running on port 80 and `APP_URL` in `.env` is correct.
- **Polling timeout**
  Recipe generation takes too long; check queue worker logs: `./vendor/bin/sail logs queue`.
- **TypeScript errors**
  Run `npm install` to ensure dependencies are up to date.
- **Styles not loading**
  Tailwind CSS v4 uses Vite plugin; restart Vite dev server after config changes.

---

## Technology Stack

### Backend
- **Laravel 12** - PHP framework with robust queue and cache systems
- **MySQL 8** - Primary database for recipes and requests
- **Redis 7** - Cache and queue backend
- **Laravel Sail** - Docker development environment
- **PHPUnit** - Testing framework

### Frontend
- **React 19** - UI library with latest features
- **TypeScript** - Type-safe JavaScript
- **Vite** - Fast build tool with HMR
- **Tailwind CSS v4** - Utility-first CSS framework
- **Axios** - HTTP client for API calls

### DevOps
- **Docker Compose** - Multi-container orchestration
- **Mailpit** - Email testing tool
- **Laravel Queues** - Background job processing
