## Краткая Сводка По Проекту

- Название проекта: `personal-app`
- Тип проекта: `web app, api, spa`
- Однострочное описание: `Проект позволяет упростить жизнь и организовать всё что мне нужно для жизни в одном месте (помодоро таймер, работа с задачами, уведомления)`
- Основные пользователи: `Один программист, в будущем могут быть простые люди`
- Стадия жизненного цикла: `prototype`
- Основная ветка: `dev`
- Важные заметки о состоянии репозитория: `Active refactor. PHP-only plugin system, no Go code. Plugin-based architecture for Telegram, mail notifications, Todoist, Yandex Calendar, Playlist.`

---

## Принципы Работы Агента

Если пользователь явно не попросил иначе, агент должен:

- предпочитать минимальное безопасное изменение, которое решает задачу;
- сохранять существующую архитектуру и naming conventions;
- обновлять тесты при изменении поведения;
- обновлять docs, config и examples, если они устаревают из-за изменений;
- проверять результат перед завершением работы;
- не делать спекулятивных рефакторингов;
- спрашивать перед разрушительными, необратимыми, дорогими или влияющими на production действиями.

### На Что Агент Должен Оптимизировать Работу

1. Correctness
2. Maintainability
3. Speed

### Чего Агент Не Должен Делать По Умолчанию

- Переписывать архитектуру без запроса.
- Добавлять новую зависимость, если задачу можно решить уже существующими зависимостями проекта.
- Редактировать сгенерированные файлы вручную, если в проекте принят соответствующий workflow.
- Игнорировать падающие проверки, связанные с изменёнными файлами или поведением.
- Совершать действия наугад в security-sensitive, billing-sensitive или compliance-sensitive областях.

## Технологический Стек

Не пишите "latest". Указывайте точные версии или поддерживаемые диапазоны.

### Основной Стек

- Язык(и): `PHP 8.2+`, `TypeScript`
- Runtime(s): `PHP 8.3`, `Node.js`
- Framework(s): `Laravel 12`, `Vue 3`
- Package manager(s): `composer`, `npm`, `task` (Taskfile)
- База(ы) данных: `PostgreSQL 16`

### Ключевые Библиотеки И Сервисы

| Область | Библиотека / сервис | Версия | Назначение | Примечания / ограничения |
| --- | --- | --- | --- | --- |
| `task-runner` | `task` | latest | Управление задачами (build, test, lint) | Все команды через `task <name>` |
| `data` | `spatie/laravel-data` | `4.18` | DTO паттерн, валидация | Использовать для DTO и API ресурсов |
| `auth` | `laravel/sanctum` | `4.0` | Авторизация | API токены |
| `testing` | `phpunit/phpunit` | `11.5+` | Тестирование | Через `task test` |
| `static-analysis` | `larastan/larastan` | `3.0` | Статический анализ | Через `task stan` |
| `code-style` | `laravel/pint` | `1.24` | Автоформатирование | PSR-12, через `task pint` |
| `frontend` | `vue` | `3.5+` | UI фреймворк | Composition API, `<script setup lang="ts">` |
| `frontend` | `pinia` | `3.0` | State management | Вместо Vuex |
| `frontend` | `vue-router` | `5.0` | Роутинг | SPA навигация |
| `frontend` | `tailwindcss` | `4.0+` | Стилизация | Utility-first CSS |
| `frontend` | `oxlint` | — | Линтер | Быстрый линтер для Vue/TS |
| `frontend` | `eslint` | `10.2+` | Линтер | TypeScript + Vue конфиг |
| `frontend` | `oxfmt` | — | Форматтер | Через `npm run format` |

---

## Архитектура

- Архитектурный стиль: `Modular monolith with clean architecture modules + plugin system`
- Высокоуровневое описание: `Each business capability owns its API layer, application logic, domain rules, and persistence adapters. Cross-cutting features (Telegram, email, Todoist, etc.) are delivered via PHP plugins.`
- Основные модули / bounded contexts: `pomodoro, user, plugin`
- Основной поток данных: `request -> route -> controller -> application service -> domain -> repository -> response mapper`

### Архитектурные Правила

- Модули должны быть независимы от модуля `User`
- Все модули должны следовать архитектуре `clean architecture`
- Нельзя обходить `domain layer` ради удобства.
- Кросс-модульная функциональность (Telegram, email уведомления, Todoist, Yandex Calendar) реализуется через плагины, а не через core.
- Плагины используют PHP interfaces из core (`TelegramAdapterInterface`, `TelegramApiClientInterface`), но сами регистрируют routes, events и bindings в своём `Plugin::boot()`.

### Структура Модуля

```
app/Modules/<Module>/
├── Application/           # Use cases, DTOs, Events, Handlers
├── Domain/                # Entities, Enums, Value Objects, Repository Interfaces
├── Infrastructure/        # Repository Implementations, Controllers, Models, Adapters
└── <Module>ServiceProvider.php
```

### Структура Плагина

```
plugins/<plugin_name>/
├── plugin.json            # Манифест (name, version, backend entry, frontend widget)
├── package.json           # Build config для frontend (Vite → UMD)
├── vite.config.ts         # Vite конфиг
├── frontend/
│   ├── index.ts           # Entry point (re-export widget)
│   ├── api/               # API клиент плагина
│   └── components/        # Vue-компоненты виджета
└── backend/
    ├── Plugin.php         # Entry point: register() + boot() (autoloader, config, routes, events, bindings)
    ├── Config/            # Конфигурация плагина
    ├── Routes/routes.php  # API маршруты плагина
    ├── Http/Controllers/  # Контроллеры плагина
    ├── Services/          # Бизнес-логика плагина
    ├── Models/            # Модели Eloquent плагина
    ├── Migrations/        # Миграции плагина
    └── Tests/             # Тесты плагина
```

## Структура Репозитория

```text
backend/                   # Backend (Laravel)
├── app/
│   ├── Core/              # Общая инфраструктура (Telegram DTO/Interfaces, MailNotifier)
│   └── Modules/
│       ├── Plugin/        # Плагин-система (Discovery, Executor, ServiceProvider)
│       ├── Pomodoro/      # Помодоро-таймер (timer, sessions, settings)
│       └── User/          # Управление пользователями (auth, plan/subscriptions)
├── config/
│   └── telegram.php       # Telegram конфиг (fallback для плагина)
├── database/
│   ├── migrations/        # Миграции БД
│   ├── seeders/           # Сидеры
│   └── factories/         # Фабрики для тестов
├── routes/
│   └── api.php            # API маршруты (auth, pomodoro, plugins, notifications, user/plan)
├── tests/
│   ├── Assertions/        # Тест-ассерты (TelegramAssertion)
│   ├── Doubles/           # Тест-дублёры (RecordingTelegramApiClient)
│   ├── SetUps/            # Тест setup traits (SetupTelegram)
│   ├── Feature/           # Feature-тесты по модулям
│   └── Unit/              # Unit-тесты
└── _docker/
    └── dev/               # Dev-окружение (php, node, nginx)

plugins/                   # PHP-плагины
├── mail_notifier/         # Email уведомления (SMTP, верификация)
├── telegram/              # Telegram бот + Pomodoro уведомления
├── todoist/               # Todoist интеграция
├── yandex_calendar/       # Yandex Calendar (CalDAV)
└── playlist/              # Плейлист для помодоро

front/                     # Frontend (Vue 3 SPA)
├── src/
│   ├── modules/           # Модули фронтенда
│   │   ├── pomodoro/      # Pomodoro таймер (settings, sessions, timer)
│   │   └── user/          # Auth, dashboard, plugins page
│   │       ├── api/       # API-клиент (auth.ts)
│   │       ├── composables/ # usePluginManagement
│   │       ├── pages/     # LoginPage, RegisterPage, DashboardPage, PluginsPage
│   │       ├── router/    # authRoutes.ts
│   │       ├── stores/    # authStore, pomodoroStore, playlistStore
│   │       └── types/     # auth.ts (User, Plan, AuthResponse)
│   ├── shared/
│   │   ├── api/           # Общий API клиент (client.ts)
│   │   ├── components/    # AppLayout.vue
│   │   └── plugins/       # PluginRegistry.ts (динамическая загрузка виджетов)
│   ├── App.vue
│   ├── main.ts
│   └── router/index.ts
└── package.json
```

---

## Основные Команды

Все команды запускаются через **task** (Taskfile):

```bash
# Запуск приложения
task up              # docker compose up -d
task up-lt           # up + localtunnel для бота
task down            # docker compose down

# Разработка
task shell           # Попасть в контейнер app
task logs            # Просмотр логов

# Проверки и тесты
task pint            # Laravel Pint (автоформатирование)
task stan            # PHPStan/Larastan (статический анализ)
task test            # PHPUnit тесты

# Перед коммитом
task prep            # pint + stan + test
```

---

## Окружение

### Docker

```bash
# Development
task up

# Production
docker compose -f docker-compose.prod.yml up -d
```

### Сервисы

| Сервис | Порт | Описание |
| --- | --- | --- |
| nginx | 3000 | Веб-сервер |
| app | - | PHP/Laravel приложение |
| node | 5173 | Vite dev server (frontend) |
| db | 5434 | PostgreSQL |

---

## Code Style

- **Стандарт**: PSR-12
- **Инструмент**: Laravel Pint
- **Команда**: `task pint`

### Статический Анализ

- **Инструмент**: PHPStan + Larastan
- **Уровень**: Максимальный (через Larastan)
- **Команда**: `task stan`

---

## Тестирование

- **Фреймворк**: PHPUnit
- **Команда**: `task test`
- **Расположение**: `backend/tests/Feature/<Module>/`, `backend/tests/Unit/<Module>/`
- **Тесты плагинов**: `plugins/<name>/backend/Tests/`

### Правила

- Писать тесты для новой функциональности
- Обновлять тесты при изменении поведения
- Feature-тесты группируются по модулям
- Тестовые doubles (RecordingTelegramApiClient) и traits (SetupTelegram, TelegramAssertion) — в `backend/tests/`

---

## API Convention

- **Формат ответов**: JSON
- **DTO**: Использовать `spatie/laravel-data` для запросов и ответов
- **Обработка ошибок**: Стандартные Laravel HTTP статусы
- **Версионирование**: `/api/v1/...`

### Основные эндпоинты

```
# Auth
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me

# Pomodoro
GET    /api/v1/pomodoro/settings
POST   /api/v1/pomodoro/settings
GET    /api/v1/pomodoro/sessions
GET    /api/v1/pomodoro/sessions/active
POST   /api/v1/pomodoro/sessions
PATCH  /api/v1/pomodoro/sessions/{id}
DELETE /api/v1/pomodoro/sessions/{id}

# Plugins
GET    /api/v1/plugins/
GET    /api/v1/plugins/enabled
POST   /api/v1/plugins/{name}/enable
POST   /api/v1/plugins/{name}/disable

# User
PUT    /api/v1/user/plan

# Telegram webhook (registered by telegram plugin)
POST   /api/v1/telegram-webhook
```

---

## Система подписок

Три плана, определяют доступ к плагинам:

| План | Enum | Помодоро | Плагины |
|------|------|----------|---------|
| `junior` | `Plan::Junior` | Да | 0 (кнопки серые, alert "Поменяйте план") |
| `middle` | `Plan::Middle` | Да | до 2 |
| `senior` | `Plan::Senior` | Да | без ограничений |

- Enum: `backend/app/Modules/User/Domain/Enums/Plan.php` (с методами `pluginLimit()`, `label()`)
- БД: строковое поле `plan` в `users` с default `'junior'`
- Модель User кастует в enum: `'plan' => Plan::class`
- При смене на junior — автоматическое отключение всех плагинов (данные/токены сохраняются)
- При смене на middle — отключение лишних плагинов (самые старые)
- Фронтенд: блок выбора плана в DashboardPage, ограничения в PluginsPage

---

## Frontend Convention

### Архитектурный стиль

- **Модульная архитектура**: Каждый bounded context (pomodoro, user и т.д.) — отдельный модуль в `src/modules/<Module>/`.
- **Модули изолированы**: Модуль не импортирует внутренности другого модуля напрямую. Общение через `shared/`.
- **Composition API + `<script setup lang="ts">`**: Единственный стиль написания компонентов.
- **Плагинские виджеты**: Динамически загружаются через `PluginRegistry` и рендерятся на DashboardPage через `<component :is="widget.component" />`.

### Структура Модуля

```
src/modules/<Module>/
├── api/                   # Функции для HTTP-запросов к backend
├── components/            # Vue-компоненты, специфичные для модуля
├── composables/           # Логика, привязанная к модулю (usePomodoroTimer и т.д.)
├── pages/                 # Страницы, подключаемые в router
├── router/                # Роуты модуля (экспортируются и регистрируются в корневом router)
├── stores/                # Pinia stores модуля
└── types/                 # TypeScript интерфейсы и типы модуля

# Примеры:
src/modules/user/types/auth.ts     — User, Plan, AuthResponse, LoginPayload, RegisterPayload
src/modules/user/stores/authStore.ts — login, register, logout, fetchUser, changePlan
src/modules/user/api/auth.ts       — login, register, logout, me, generateTelegramLinkToken, updatePlan
```

### Правила

- **Импорты**: Использовать path alias `@/` (указывает на `src/`).
- **Стили**: TailwindCSS. Избегать inline-стилей и scoped CSS, если можно обойтись utility-классами.
- **Сторы**: Одна доменная область — один Pinia store. Использовать setup-стиль сторов.
- **Типизация**: Всё типизировать строго. Никаких `any` без крайней необходимости.
- **API**: Централизовать вызовы backend в `api/` каждого модуля. Использовать `apiClient` из `shared/api/client.ts`.
- **Роутер**: Каждый модуль экспортирует свои роуты; корневой `router/index.ts` импортирует и объединяет их.
- **Enum типы**: Использовать `type` (не `interface`) для union-типов (например, `type Plan = 'junior' | 'middle' | 'senior'`).

---

### How to write code

You are an expert in Laravel, PHP, and related web development technologies.

  Core Principles
  - Write concise, technical responses with accurate PHP/Laravel examples.
  - Prioritize SOLID principles for object-oriented programming and clean architecture.
  - Follow PHP and Laravel best practices, ensuring consistency and readability.
  - Design for scalability and maintainability, ensuring the system can grow with ease.
  - Prefer iteration and modularization over duplication to promote code reuse.
  - Use consistent and descriptive names for variables, methods, and classes to improve readability.

  Dependencies
  - Composer for dependency management
  - PHP 8.3+
  - Laravel 12+

  PHP and Laravel Standards
  - Leverage PHP 8.3+ features when appropriate (e.g., typed properties, match expressions, backed enums).
  - Adhere to PSR-12 coding standards for consistent code style.
  - Always use strict typing: declare(strict_types=1);
  - Utilize Laravel's built-in features and helpers to maximize efficiency.
  - Follow Laravel's directory structure and file naming conventions.
  - Implement robust error handling and logging:
    > Use Laravel's exception handling and logging features.
    > Create custom exceptions when necessary.
    > Employ try-catch blocks for expected exceptions.
  - Use Laravel's validation features for form and request data.
  - Implement middleware for request filtering and modification.
  - Utilize Laravel's Eloquent ORM for database interactions.
  - Use Laravel's query builder for complex database operations.
  - Create and maintain proper database migrations and seeders.
  - Use backed enums (e.g., `Plan::Junior`) for domain constants instead of raw strings.

  Laravel Best Practices
  - Use Eloquent ORM and Query Builder over raw SQL queries when possible
  - Implement Repository and Service patterns for better code organization and reusability
  - Utilize Laravel's built-in authentication and authorization features (Sanctum, Policies)
  - Leverage Laravel's caching mechanisms (Redis, Memcached) for improved performance
  - Implement comprehensive testing using PHPUnit for unit and feature tests
  - Use API resources and versioning for building robust and maintainable APIs
  - Implement proper error handling and logging using Laravel's exception handler and logging facade
  - Utilize Laravel's validation features, including Form Requests, for data integrity
  - Implement database indexing and use Laravel's query optimization features for better performance
  - Implement proper security measures, including CSRF protection, XSS prevention, and input sanitization

  Code Architecture
    * Naming Conventions:
      - Use consistent naming conventions for folders, classes, and files.
      - Follow Laravel's conventions: singular for models, plural for controllers.
      - Use PascalCase for class names, camelCase for method names, and snake_case for database columns.
    * Controller Design:
      - Controllers should be final classes to prevent inheritance.
      - Make controllers read-only (i.e., no property mutations).
      - Avoid injecting dependencies directly into controllers. Instead, use method injection or service classes.
    * Model Design:
      - Models should be final classes to ensure data integrity and prevent unexpected behavior from inheritance.
      - Use backed enums for domain constants (e.g., `Plan::class` in casts).
    * Services:
      - Create a Services folder within the app directory.
      - Service classes should be final and read-only.
      - Use services for complex business logic, keeping controllers thin.
    * Plugin System:
      - Cross-cutting features are delivered via PHP plugins in `plugins/<name>/`.
      - Each plugin has a `Plugin.php` implementing `PluginInterface` with `register()` and `boot()` methods.
      - Plugins register their own routes, event listeners, container bindings, and artisan commands in `boot()`.
      - Plugin migrations are auto-discovered from `plugins/*/backend/Migrations/`.
      - Frontend widgets are loaded dynamically via `PluginRegistry` and rendered on the dashboard.
    * Type Declarations:
      - Always use explicit return type declarations for methods and functions.
      - Use appropriate PHP type hints for method parameters.
      - Leverage PHP 8.3+ features like union types, nullable types, and backed enums.

  Key points
  - Follow Laravel's architecture for clear separation of business logic, data, and presentation layers.
  - Implement request validation using Form Requests to ensure secure and validated data inputs.
  - Use Laravel's built-in authentication system, including Laravel Sanctum for API token management.
  - Ensure the REST API follows Laravel standards, using API Resources for structured and consistent responses.
  - Leverage event listeners and plugin hooks to decouple cross-cutting logic.
  - Use Eloquent ORM for database interactions, enforcing relationships and optimizing queries.
  - Implement proper error handling and logging using Laravel's exception handler and logging features.

---
