# Event-Driven Multi-Country HR Platform

A real-time, event-driven backend platform built with Laravel, demonstrating microservice architecture with RabbitMQ messaging, WebSocket broadcasting, intelligent caching, and server-driven UI patterns.

## Technology Stack

- **Framework**: Laravel 12 (PHP 8.4)
- **Database**: PostgreSQL 16
- **Message Queue**: RabbitMQ 3.13 (AMQP topic exchange)
- **Cache**: Redis 7
- **WebSockets**: Soketi (Pusher-compatible, self-hosted)
- **Containerization**: Docker Compose

## Architecture

```
┌──────────────┐    events    ┌──────────────┐    events    ┌──────────────────┐
│  HR Service  │─────────────▶│   RabbitMQ   │─────────────▶│   HubService     │
│  (Port 8001) │              │ (Port 15672) │              │   (Port 8002)    │
│              │              │              │              │                  │
│ • CRUD API   │              │ • Topic      │              │ • Checklist API  │
│ • Validation │              │   Exchange   │              │ • Steps API      │
│ • Events     │              │ • Queues     │              │ • Schema API     │
└──────────────┘              └──────────────┘              │ • Employees API  │
                                                            └────────┬─────────┘
                                                                     │
                                                         ┌───────────┴──────────┐
                                                         │                      │
                                                    ┌────▼─────┐         ┌──────▼──────┐
                                                    │  Redis   │         │   Soketi    │
                                                    │  Cache   │         │  WebSocket  │
                                                    │          │         │ (Port 6001) │
                                                    └──────────┘         └─────────────┘
```

### Data Flow

1. Client creates/updates/deletes employee via **HR Service** REST API
2. HR Service publishes event to **RabbitMQ** topic exchange (`employee_events`)
3. **Hub Worker** consumes event from queue, routes to appropriate handler
4. Handler invalidates **Redis** cache and broadcasts via **Soketi**
5. Frontend clients receive real-time updates via WebSocket channels

## Quick Start

### Prerequisites
- Docker & Docker Compose

### Start the System

```bash
docker-compose up -d
```

This starts all 7 services:
- **HR Service** → http://localhost:8001
- **Hub Service** → http://localhost:8002
- **RabbitMQ Management** → http://localhost:15672 (guest/guest)
- **Soketi WebSocket** → ws://localhost:6001

### Test the APIs

```bash
# Create a USA employee
curl -X POST http://localhost:8001/api/employees \
  -H "Content-Type: application/json" \
  -d '{"name":"John","last_name":"Doe","salary":75000,"country":"USA","ssn":"123-45-6789","address":"123 Main St, NY"}'

# Get checklist for USA
curl http://localhost:8002/api/checklists?country=USA

# Get navigation steps for Germany
curl http://localhost:8002/api/steps?country=Germany

# Get dashboard schema for USA
curl http://localhost:8002/api/schema/dashboard?country=USA

# Get employees with country-specific columns
curl http://localhost:8002/api/employees?country=USA
```

### Test WebSockets

Open `websocket-test.html` in a browser to see real-time updates when employee data changes.

### Run Tests

```bash
# HR Service tests
docker-compose exec hr-service php artisan test

# Hub Service tests
docker-compose exec hub-service php artisan test
```

## API Endpoints

### HR Service (Port 8001)

- `GET /api/employees` — List employees (filterable by `country`)
- `POST /api/employees` — Create employee
- `GET /api/employees/{id}` — Get employee
- `PUT /api/employees/{id}` — Update employee
- `DELETE /api/employees/{id}` — Delete employee

### Hub Service (Port 8002)

- `GET /api/checklists?country=` — Employee data completeness checklist
- `GET /api/steps?country=` — Navigation steps configuration
- `GET /api/employees?country=` — Employee list with country-specific columns
- `GET /api/schema/{step_id}?country=` — Dashboard widget configuration

## Design Decisions

### 1. Strategy Pattern for Country Validators
Each country has its own validator class implementing `CountryValidatorInterface`. Adding a new country requires only creating a new validator class and registering it in the factory — no existing code changes needed.

### 2. Topic Exchange in RabbitMQ
Using a topic exchange with routing keys (`employee.created.usa`, `employee.updated.germany`) allows flexible routing. The Hub Service binds with `employee.*.*` to receive all events, but could easily be configured to only process specific countries.

### 3. Redis for Caching
Chosen over Memcached for: pattern-based key deletion (critical for cache invalidation), native Laravel integration, versatile data structures, and it can double as a queue driver.

### 4. Soketi over Pusher
Self-hosted Soketi in Docker Compose means no external dependencies, full control, and the system runs completely offline. It's Pusher-protocol compatible, so the frontend uses the standard Pusher JS client.

### 5. Cache-Aside Pattern
The HubService never directly accesses the HR Service's database. It fetches via the HR Service API and caches in Redis. Cache is invalidated when RabbitMQ events arrive, ensuring eventual consistency.

### 6. Config-Driven Server UI
Steps, columns, and dashboard widgets are defined in `config/countries.php`. Adding a new country's UI is a config change, not a code change.

### Cache Key Structure

- `employees:{country}:all` — TTL: 1 hour, invalidated on any employee event
- `employees:{country}:page:{n}:per_page:{n}` — TTL: 1 hour, invalidated on any employee event
- `checklist:{country}` — TTL: 30 min, invalidated on any employee event
- `employee:{id}` — TTL: 1 hour, invalidated on update/delete

### WebSocket Channels

- `country.{country}` — Public channel for employee list changes
- `checklist.{country}` — Public channel for checklist data updates
- `employee.{id}` — Public channel for individual employee changes

## Project Structure

```
multi-country-platform/
├── docker-compose.yml
├── docker/
│   ├── Dockerfile
│   ├── entrypoint.sh
│   ├── nginx/
│   ├── postgres/init.sql
│   └── supervisor/
├── hr-service/                 # Employee CRUD microservice
│   ├── app/
│   │   ├── Enums/CountryEnum.php
│   │   ├── Http/Controllers/Api/EmployeeController.php
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/EmployeeResource.php
│   │   ├── Models/Employee.php
│   │   └── Services/RabbitMQService.php
│   └── tests/
├── hub-service/                # Main orchestration service
│   ├── app/
│   │   ├── Console/Commands/ConsumeRabbitMQEvents.php
│   │   ├── Contracts/
│   │   ├── Enums/CountryEnum.php
│   │   ├── EventHandlers/
│   │   ├── Events/
│   │   ├── Http/Controllers/Api/
│   │   ├── Services/
│   │   └── Validators/
│   ├── config/countries.php
│   └── tests/
├── websocket-test.html
└── README.md
```
