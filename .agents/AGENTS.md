## Краткая Сводка По Проекту

- Название проекта: `personal-app`
- Тип проекта: `web app, api, spa`
- Однострочное описание: `Проект позволяет упростить жизнь и организовать всё что мне нужно для жизни в одном месте (помодоро таймер, работа с задачами, уведомления)`
- Основные пользователи: `Один программист, в будущем могут быть простые люди`
- Стадия жизненного цикла: `prototype`
- Основная ветка: `dev`
- Важные заметки о состоянии репозитория: `active refactor and make features. The project mainly works with third-party services, rather than doing anything itself. `

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

- Архитектурный стиль: `Modular monolith with clean architecture modules`
- Высокоуровневое описание: `Each business capability owns its API layer, application logic, domain rules, and persistence adapters`
- Основные модули / bounded contexts: `pomodoro, user`
- Основной поток данных: `request -> route -> controller -> application service -> domain -> repository -> response mapper`

### Архитектурные Правила

- Модули должны быть независимы от модуля `User`
- Все модули должны следовать архитектуре `clean architecture`
- Нельзя обходить `domain layer` ради удобства.

### Структура Модуля

```
src/app/Modules/<Module>/
├── Application/           # Use cases, DTOs, Validators
├── Domain/                # Entities, Value Objects, Repository Interfaces
├── Infrastructure/        # Repository Implementations, External Services
└── <Module>ServiceProvider.php
```

## Структура Репозитория

```text
src/                       # Backend (Laravel)
├── app/
│   ├── Core/              # Общие модули (Telegram и др.)
│   └── Modules/
│       ├── Pomodoro/      # Помодоро-таймер
│       └── User/          # Управление пользователями
├── database/
│   ├── migrations/        # Миграции БД
│   ├── seeders/           # Сидеры
│   └── factories/         # Фабрики для тестов
├── tests/
│   ├── Feature/           # Feature-тесты по модулям
│   └── Unit/              # Unit-тесты
└── _docker/
    ├── dev/               # Dev-окружение
    └── prod/              # Production-окружение

front/                     # Frontend (Vue 3 SPA)
├── src/
│   ├── modules/           # Модули фронтенда (pomodoro, user, ...)
│   │   └── <Module>/
│   │       ├── api/       # API-клиент, запросы к backend
│   │       ├── components/# Vue-компоненты модуля
│   │       ├── composables/# Переиспользуемая логика (useXxx)
│   │       ├── pages/     # Страницы/вьюхи модуля
│   │       ├── router/    # Роуты модуля
│   │       ├── stores/    # Pinia-сторы модуля
│   │       └── types/     # TypeScript типы модуля
│   ├── shared/            # Общий код между модулями
│   │   ├── components/    # Переиспользуемые UI-компоненты
│   │   ├── composables/   # Общие composables
│   │   ├── utils/         # Утилиты, хелперы
│   │   └── types/         # Глобальные типы
│   ├── App.vue
│   ├── main.ts
│   └── router/
│       └── index.ts       # Корневой роутер (объединяет модули)
├── public/
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
- **Расположение**: `src/tests/Feature/<Module>/`, `src/tests/Unit/<Module>/`

### Правила

- Писать тесты для новой функциональности
- Обновлять тесты при изменении поведения
- Feature-тесты группируются по модулям

---

## API Convention

- **Формат ответов**: JSON
- **DTO**: Использовать `spatie/laravel-data` для запросов и ответов
- **Обработка ошибок**: Стандартные Laravel HTTP статусы

---

## Frontend Convention

### Архитектурный стиль

- **Модульная архитектура**: Каждый bounded context (pomodoro, user и т.д.) — отдельный модуль в `src/modules/<Module>/`.
- **Модули изолированы**: Модуль не импортирует внутренности другого модуля напрямую. Общение через `shared/`.
- **Composition API + `<script setup lang="ts">`**: Единственный стиль написания компонентов.

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
```

### Правила

- **Импорты**: Использовать path alias `@/` (указывает на `src/`).
- **Стили**: TailwindCSS. Избегать inline-стилей и scoped CSS, если можно обойтись utility-классами.
- **Сторы**: Одна доменная область — один Pinia store. Использовать setup-стиль сторов.
- **Типизация**: Всё типизировать строго. Никаких `any` без крайней необходимости.
- **API**: Централизовать вызовы backend в `api/` каждого модуля. Использовать `fetch` или `axios` (если добавлен).
- **Роутер**: Каждый модуль экспортирует свои роуты; корневой `router/index.ts` импортирует и объединяет их.

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
  - Laravel 11.0+

  PHP and Laravel Standards
  - Leverage PHP 8.3+ features when appropriate (e.g., typed properties, match expressions).
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


  Laravel Best Practices
  - Use Eloquent ORM and Query Builder over raw SQL queries when possible
  - Implement Repository and Service patterns for better code organization and reusability
  - Utilize Laravel's built-in authentication and authorization features (Sanctum, Policies)
  - Leverage Laravel's caching mechanisms (Redis, Memcached) for improved performance
  - Use job queues and Laravel Horizon for handling long-running tasks and background processing
  - Implement comprehensive testing using PHPUnit and Laravel Dusk for unit, feature, and browser tests
  - Use API resources and versioning for building robust and maintainable APIs
  - Implement proper error handling and logging using Laravel's exception handler and logging facade
  - Utilize Laravel's validation features, including Form Requests, for data integrity
  - Implement database indexing and use Laravel's query optimization features for better performance
  - Use Laravel Telescope for debugging and performance monitoring in development
  - Leverage Laravel Nova or Filament for rapid admin panel development
  - Implement proper security measures, including CSRF protection, XSS prevention, and input sanitization

  Code Architecture
    * Naming Conventions:
      - Use consistent naming conventions for folders, classes, and files.
      - Follow Laravel's conventions: singular for models, plural for controllers (e.g., User.php, UsersController.php).
      - Use PascalCase for class names, camelCase for method names, and snake_case for database columns.
    * Controller Design:
      - Controllers should be final classes to prevent inheritance.
      - Make controllers read-only (i.e., no property mutations).
      - Avoid injecting dependencies directly into controllers. Instead, use method injection or service classes.
    * Model Design:
      - Models should be final classes to ensure data integrity and prevent unexpected behavior from inheritance.
    * Services:
      - Create a Services folder within the app directory.
      - Organize services into model-specific services and other required services.
      - Service classes should be final and read-only.
      - Use services for complex business logic, keeping controllers thin.
    * Routing:
      - Maintain consistent and organized routes.
      - Create separate route files for each major model or feature area.
      - Group related routes together (e.g., all user-related routes in routes/user.php).
    * Type Declarations:
      - Always use explicit return type declarations for methods and functions.
      - Use appropriate PHP type hints for method parameters.
      - Leverage PHP 8.3+ features like union types and nullable types when necessary.
    * Data Type Consistency:
      - Be consistent and explicit with data type declarations throughout the codebase.
      - Use type hints for properties, method parameters, and return types.
      - Leverage PHP's strict typing to catch type-related errors early.
    * Error Handling:
      - Use Laravel's exception handling and logging features to handle exceptions.
      - Create custom exceptions when necessary.
      - Use try-catch blocks for expected exceptions.
      - Handle exceptions gracefully and return appropriate responses.

  Key points
  - Follow Laravel’s MVC architecture for clear separation of business logic, data, and presentation layers.
  - Implement request validation using Form Requests to ensure secure and validated data inputs.
  - Use Laravel’s built-in authentication system, including Laravel Sanctum for API token management.
  - Ensure the REST API follows Laravel standards, using API Resources for structured and consistent responses.
  - Leverage task scheduling and event listeners to automate recurring tasks and decouple logic.
  - Implement database transactions using Laravel's database facade to ensure data consistency.
  - Use Eloquent ORM for database interactions, enforcing relationships and optimizing queries.
  - Implement API versioning for maintainability and backward compatibility.
  - Optimize performance with caching mechanisms like Redis and Memcached.
  - Ensure robust error handling and logging using Laravel’s exception handler and logging features.

---

