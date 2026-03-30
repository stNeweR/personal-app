## Краткая Сводка По Проекту

- Название проекта: `personal-app`
- Тип проекта: `web app, api`
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

- Язык(и): `PHP 8.2+`
- Runtime(s): `PHP 8.3`
- Framework(s): `Laravel 12`
- Package manager(s): `composer`, `task` (Taskfile)
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
src/
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
