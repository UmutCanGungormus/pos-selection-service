# POS Selection Service

A microservice that selects the lowest-cost POS terminal for payment transactions based on card type, brand, installment, and currency.

## Architecture Overview

```
                         +-------------------+
                         |      Client       |
                         +--------+----------+
                                  |
                                  v
                         +--------+----------+
                         |   Nginx (:8080)   |
                         +--------+----------+
                                  |
                                  v
                   +-----------------------------+
                   |   Laravel 13 (PHP-FPM 8.4)  |
                   +----+--------+--------+------+
                        |        |        |
              +---------+  +-----+-----+  +----------+
              v            v           v              v
        +---------+  +---------+  +-----------+  +-----------+
        |  MySQL  |  |  Redis  |  |  Horizon  |  |  Graylog  |
        |  8.0    |  |  7      |  |  (Queue)  |  |  (GELF)   |
        +---------+  +---------+  +-----------+  +-----------+
        Persistence   Cache        Worker         Centralized
                      Queue        Monitoring     Logging
                      Session
```

## Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel | 13 | Framework |
| MySQL | 8.0 | Relational database |
| Redis | 7 | Cache, queue, session driver |
| Laravel Horizon | - | Queue monitoring and management |
| Nginx | Alpine | Reverse proxy |
| Docker | - | Containerized deployment |
| Graylog + OpenSearch | 6.1 / 2.x | Centralized log management (GELF/UDP) |
| Pest PHP | 3.x | Testing framework |
| Laravel Pint | - | Code style enforcement (Laravel preset) |

## Design Patterns and Principles

- **Repository Pattern** -- Abstracts data access and enables testability. `AbstractEloquentRepository` base class with `PosRateRepository` implementation.
- **Strategy Pattern** -- `PosSelectionStrategyInterface` with `LowestCostSelectionStrategy` implementation. Selection algorithms are swappable without modifying consuming code.
- **Provider Pattern** -- `PosRateProviderInterface` with `MockApiPosRateProvider` implementation. External data sources are pluggable.
- **DTO Pattern** -- `PosSelectionCriteria` and `PosSelectionOutcome` as domain-intent named value objects with readonly properties.
- **Query Filters** -- Dynamic HTTP filtering with allowlist security via `PosRateFilters`.
- **Job Middleware** -- `RateLimitedSync` for Redis-based throttling of queue jobs.
- **SOLID Principles** -- Single Responsibility (separate providers, services, strategies), Open/Closed (strategy swap without modification), Dependency Inversion (all core dependencies bound through interfaces).

## Project Structure

```
app/
├── Contracts/          # Interfaces (Provider, Strategy, Repository)
├── DTOs/               # Value objects (Criteria, Outcome)
├── Enums/              # CardType, Currency
├── Exceptions/         # Domain exceptions
├── Filters/            # Query filter system
├── Http/
│   ├── Controllers/    # Thin controllers
│   ├── Requests/       # Form request validation
│   └── Resources/      # API resource transformers
├── Jobs/               # Queue jobs + middleware
├── Logging/            # Graylog GELF factory
├── Models/             # Eloquent models
├── Providers/          # Service providers (Pos, Repository, Horizon, Macro)
├── Repositories/       # Repository pattern (Abstract + Contracts + Eloquent)
├── Services/           # Business logic (Sync, Selection, API provider)
└── Traits/             # Reusable traits (Filterable)
```

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/pos/select` | Select lowest-cost POS for a transaction |
| GET | `/api/pos/rates` | List POS rates (paginated, filterable) |
| POST | `/api/pos/sync` | Dispatch async rate sync job via Horizon |

### POS Selection Example

**Request:**

```json
{
  "amount": 1000,
  "installment": 6,
  "currency": "TRY",
  "card_type": "credit"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "filters": {
      "installment": 6,
      "currency": "TRY",
      "card_type": "credit",
      "card_brand": null
    },
    "overall_min": {
      "pos_name": "Garanti",
      "card_type": "credit",
      "card_brand": "bonus",
      "installment": 6,
      "currency": "TRY",
      "commission_rate": 0.0270
    },
    "price": 27.00,
    "payable_total": 1027.00
  }
}
```

### Cost Calculation

```
POS Cost = max(Amount x Commission Rate, Minimum Fee)
```

The `price` field represents the commission cost, and `payable_total` is the sum of `amount` and `price`. When multiple POS providers have equal costs, the one with the higher `priority` value is selected.

## Getting Started

### Prerequisites

- Docker and Docker Compose

### Installation

```bash
git clone <repo-url>
cd pos-selection-service
cp .env.example .env
docker compose up -d --build
```

That is all. The service will:

1. Build the PHP 8.4 image with required extensions
2. Start MySQL and Redis, waiting for health checks to pass
3. Run migrations automatically (init container pattern)
4. Cache config, routes, and events
5. Start PHP-FPM, Nginx, Horizon, and Scheduler

### Verify

```bash
# Check all services are running
docker compose ps

# Install dev dependencies (required once, since the container ships with --no-dev)
docker exec pos-app composer install --dev

# Run the test suite
docker exec pos-app php artisan test

# Dispatch POS rate sync job
curl -X POST http://localhost:8080/api/pos/sync

# Select the best POS for a transaction
curl -X POST http://localhost:8080/api/pos/select \
  -H "Content-Type: application/json" \
  -d '{"amount":1000,"installment":6,"currency":"TRY","card_type":"credit"}'
```

### Dashboards

| Dashboard | URL | Credentials |
|---|---|---|
| Horizon | http://localhost:8080/horizon | No auth (local env) |
| Graylog | http://localhost:9001 | `admin` / `admin` |

## Code Quality

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code style enforcement with the default Laravel preset.

```bash
# Fix code style
./vendor/bin/pint

# Check for violations without fixing
./vendor/bin/pint --test
```

## Testing

```bash
docker exec pos-app php artisan test
```

77 tests, 212 assertions covering:

- **Unit:** Models, Repositories (Abstract + PosRate), Services, Job Middleware, Logging
- **Feature:** API endpoints, POS selection algorithm, rate sync, pagination, filtering, validation

## Docker Services

| Service | Container | Purpose |
|---|---|---|
| app | pos-app | PHP-FPM application server |
| nginx | pos-nginx | Reverse proxy (:8080) |
| horizon | pos-horizon | Queue worker (Laravel Horizon) |
| scheduler | pos-scheduler | Cron job runner |
| mysql | pos-mysql | Database (:3307) |
| redis | pos-redis | Cache / Queue / Session (:6380) |
| migrate | pos-migrate | One-shot migration runner |
| graylog | pos-graylog | Log management (:9000) |
| mongodb | pos-mongodb | Graylog metadata store |
| opensearch | pos-opensearch | Graylog search engine |

## Environment Variables

Key configuration with defaults:

```env
# Application
APP_ENV=local
APP_KEY=

# Database
DB_DATABASE=pos_selection
DB_USERNAME=pos_user
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=

# Docker Ports
FORWARD_NGINX_PORT=8080
FORWARD_MYSQL_PORT=3307
FORWARD_REDIS_PORT=6380

# Logging
LOG_CHANNEL=stack
LOG_STACK=stderr,graylog

# Horizon
HORIZON_PREFIX=pos-horizon:
```

## External API

POS rates are fetched from the mock API provided by PayTR:

```
GET https://6899a45bfed141b96ba02e4f.mockapi.io/paytr/ratios
```

The sync job runs hourly via the scheduler and can be triggered manually via `POST /api/pos/sync`. The endpoint dispatches the job to the queue asynchronously, benefiting from Horizon's retry logic, rate limiting, and uniqueness guarantees.

## License

MIT
