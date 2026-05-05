# TaskFlow

TaskFlow is a pet project for task management, project collaboration, and asynchronous notifications. The repository is prepared as a monorepo with Laravel for the API, Vue 3 for the frontend, Go for the notification microservice, and Docker Compose for local orchestration.

## Stack

- Laravel 10 + Sanctum-ready API foundation
- Vue 3 + Vite + Vue Router + Pinia + Axios
- Go notification service with RabbitMQ consumer and healthcheck
- PostgreSQL 16
- Redis 7
- RabbitMQ 3 with Management UI
- Nginx reverse proxy
- Docker Compose

## Repository structure

```text
taskflow/
├── backend/
├── frontend/
├── notification-service/
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
├── .env.example
└── README.md
```

## What is already prepared

- Real Laravel backend scaffold in `backend/`
- Real Vue 3 frontend scaffold in `frontend/`
- PostgreSQL-oriented environment defaults
- Domain schema for users, projects, tasks, comments, and notifications
- RabbitMQ config for Laravel and a working Go consumer skeleton
- Basic SPA layout with routes for dashboard, projects, tasks, notifications, login, and registration
- Dockerfiles and `docker-compose.yml` to run the stack locally

## Local startup

1. Copy the root environment file.
2. Run `docker compose up --build`.
3. Open [http://localhost](http://localhost).

Extra endpoints:

- API health: [http://localhost/api/health](http://localhost/api/health)
- RabbitMQ UI: [http://localhost:15672](http://localhost:15672)

## Important environment values

- `APP_URL`
- `FRONTEND_URL`
- `DB_*`
- `REDIS_*`
- `RABBITMQ_*`
- `POSTGRES_*`

Backend-specific defaults live in [backend/.env.example](/C:/Users/DivvOne/Documents/New%20project%204/backend/.env.example).

## Backend notes

- The backend now targets PostgreSQL by default.
- Sanctum is installed.
- RabbitMQ publishing library `php-amqplib/php-amqplib` is installed.
- API starter routes are available in [backend/routes/api.php](/C:/Users/DivvOne/Documents/New%20project%204/backend/routes/api.php).
- Domain models and migrations are prepared for the MVP schema.

## Go notification service

The Go service:

- connects to RabbitMQ
- listens to the `notifications` queue
- validates incoming payloads
- stores notifications in PostgreSQL
- exposes `GET /health` on port `8081`
- supports graceful shutdown

Main entrypoint: [notification-service/cmd/notification-service/main.go](/C:/Users/DivvOne/Documents/New%20project%204/notification-service/cmd/notification-service/main.go)

## Frontend notes

The frontend currently includes:

- app shell and dashboard starter
- Vue Router navigation
- pages for projects, tasks, notifications, login, and registration
- styling that is ready to evolve into the real product UI

Main files:

- [frontend/src/App.vue](/C:/Users/DivvOne/Documents/New%20project%204/frontend/src/App.vue)
- [frontend/src/router/index.js](/C:/Users/DivvOne/Documents/New%20project%204/frontend/src/router/index.js)
- [frontend/src/style.css](/C:/Users/DivvOne/Documents/New%20project%204/frontend/src/style.css)

## Suggested next steps

1. Implement auth endpoints: register, login, logout, me.
2. Add policies and permissions for Admin, Manager, and User.
3. Build project, task, and comment CRUD controllers with request validation.
4. Publish RabbitMQ events on task assignment, status changes, and comments.
5. Replace frontend mock data with Axios and Pinia stores.
6. Add Laravel feature tests and Go unit tests.
