# TaskFlow

TaskFlow — это pet-project для управления задачами, проектами и асинхронными уведомлениями. Репозиторий организован как монорепозиторий: Laravel отвечает за API, Vue 3 за frontend, Go за сервис уведомлений, а Docker Compose за локальную оркестрацию.

## Стек

- Laravel 10 + Sanctum для API и авторизации
- Vue 3 + Vite + Vue Router + Pinia + Axios
- Go-сервис уведомлений с consumer для RabbitMQ и healthcheck endpoint
- PostgreSQL 16
- Redis 7
- RabbitMQ 3 с Management UI
- Nginx как reverse proxy
- Docker Compose

## Структура репозитория

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

## Что уже подготовлено

- Реальный Laravel backend в `backend/`
- Реальный Vue 3 frontend в `frontend/`
- Конфигурация по умолчанию под PostgreSQL
- Доменная схема для пользователей, проектов, задач, комментариев и уведомлений
- Конфигурация RabbitMQ для Laravel и рабочий каркас Go consumer-сервиса
- Базовый SPA-интерфейс с маршрутами dashboard, projects, tasks, notifications, login и register
- Dockerfiles и `docker-compose.yml` для локального запуска
- Рабочая регистрация, авторизация, CRUD проектов и CRUD задач

## Локальный запуск

1. Скопируй корневой env-файл.
2. Выполни `docker compose up --build`.
3. Открой приложение.

Основной адрес:

- [http://localhost:8080](http://localhost:8080)

Дополнительные адреса:

- Проверка API: [http://localhost:8080/api/health](http://localhost:8080/api/health)
- Сводка dashboard из backend: [http://localhost:8080/api/dashboard/summary](http://localhost:8080/api/dashboard/summary)
- RabbitMQ UI: [http://localhost:15672](http://localhost:15672)
- Vite dev server frontend: [http://localhost:5173](http://localhost:5173)

## Важные переменные окружения

- `APP_URL`
- `FRONTEND_URL`
- `DB_*`
- `REDIS_*`
- `RABBITMQ_*`
- `POSTGRES_*`

Backend-значения по умолчанию находятся в [backend/.env.example](/backend/.env.example).

## Backend

- Backend по умолчанию работает с PostgreSQL.
- Sanctum уже установлен и используется для токеновой авторизации.
- Установлена библиотека для RabbitMQ: `php-amqplib/php-amqplib`.
- Основные API-маршруты находятся в [backend/routes/api.php](/backend/routes/api.php).
- Подготовлены модели и миграции для MVP-схемы.
- Уже реализованы:
    - `register`
    - `login`
    - `logout`
    - `me`
    - CRUD проектов
    - CRUD задач

## Go-сервис уведомлений

Go-сервис:

- подключается к RabbitMQ
- слушает очередь `notifications`
- валидирует входящий payload
- сохраняет уведомления в PostgreSQL
- отдает `GET /health` на порту `8081`
- поддерживает graceful shutdown

Точка входа: [notification-service/cmd/notification-service/main.go](/notification-service/cmd/notification-service/main.go)

## Frontend

Frontend сейчас включает:

- общий app shell
- dashboard с живым запросом в Laravel API
- страницы для проектов, задач, уведомлений, входа и регистрации
- подключенную авторизацию
- формы создания, редактирования и удаления проектов и задач

Основные файлы:

- [frontend/src/App.vue](/frontend/src/App.vue)
- [frontend/src/router/index.js](/frontend/src/router/index.js)
- [frontend/src/stores/auth.js](/frontend/src/stores/auth.js)
- [frontend/src/views/ProjectsView.vue](/frontend/src/views/ProjectsView.vue)
- [frontend/src/views/TasksView.vue](/frontend/src/views/TasksView.vue)
- [frontend/src/style.css](/frontend/src/style.css)

## Что можно делать сейчас

- зарегистрироваться
- войти в систему
- выйти из системы
- просматривать dashboard
- создавать проекты
- редактировать проекты
- удалять проекты
- создавать задачи
- редактировать задачи
- удалять задачи

## Следующие шаги

1. Добавить комментарии к задачам.
2. Реализовать роли и права доступа для `Admin`, `Manager` и `User`.
3. Добавить отдельные действия смены статуса задачи.
4. Публиковать события в RabbitMQ при создании и обновлении задач.
5. Подключить реальные уведомления на frontend.
6. Добавить feature-тесты Laravel и unit-тесты Go.
