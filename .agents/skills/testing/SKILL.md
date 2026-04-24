---
name: testing
description: Writing and reviewing tests (unit, integration, functional). Focus on test coverage, assertions, mocking, fixtures, and test best practices.
---

# Testing Code Review & Creation

## Режимы работы

### 1. Написание тестов для файла
Когда пользователь просит написать тесты для конкретного файла:
- Прочитайте указанный файл
- Определите тип файла (controller, service, model, utility, etc.)
- Выявите тестируемые методы и их зависимости
- Определите существующую тестовую инфраструктуру проекта (фреймворк, структура)
- Создайте тестовый файл с полным покрытием публичных методов
- Включите тесты для:
  - Позитивных сценариев (happy path)
  - Негативных сценариев (ошибки, исключения)
  - Граничных случаев (edge cases)

### 2. Проверка существующих тестов
Когда пользователь просит проверить существующие тесты:
- Прочитайте тестовые файлы
- Оцените покрытие кода
- Проверьте качество тестов по критериям из раздела "Критерии качества тестов"
- Выявите проблемы и предложите улучшения

### 3. Создание тестовой инфраструктуры
Когда в проекте отсутствует тестовая инфраструктура:
- Определите стек технологий проекта
- Предложите подходящие тестовые фреймворки
- Создайте конфигурационные файлы
- Настройте CI/CD интеграцию (если требуется)

## Типы тестов

### Unit-тесты (Модульные)

**Что тестировать:**
- Отдельные методы и функции
- Классы в изоляции от зависимостей
- Чистую бизнес-логику

**Когда использовать:**
- Тестирование доменных сущностей
- Тестирование сервисов с мокированными зависимостями
- Тестирование utility-функций

**Пример структуры:**
```php
// Для класса UserService
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userService = new UserService($this->userRepositoryMock);
    }

    public function testCreateUser_Success(): void
    {
        // Arrange
        $userData = ['email' => 'test@example.com'];
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        // Act
        $user = $this->userService->create($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }
}
```

### Integration-тесты (Интеграционные)

**Что тестировать:**
- Взаимодействие между компонентами
- Интеграцию с БД, внешними API
- End-to-end сценарии для отдельных модулей

**Когда использовать:**
- Тестирование repository слоя с реальной БД
- Тестирование HTTP-контроллеров
- Тестирование интеграции с внешними сервисами

**Пример структуры:**
```php
class UserRepositoryIntegrationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getTestDatabaseConnection();
    }

    public function testFindByEmail_ExistingUser(): void
    {
        // Arrange
        $this->insertTestUser('test@example.com');
        $repository = new UserRepository($this->connection);

        // Act
        $user = $repository->findByEmail('test@example.com');

        // Assert
        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }
}
```

### Functional-тесты (Функциональные)

**Что тестировать:**
- Полные сценарии использования
- HTTP-запросы и ответы
- Поведение системы с точки зрения пользователя

**Когда использовать:**
- Тестирование API endpoints
- Тестирование веб-интерфейса
- Тестирование бизнес-сценариев

**Пример структуры:**
```php
class UserApiFunctionalTest extends ApiTestCase
{
    public function testCreateUserEndpoint(): void
    {
        // Act
        $response = $this->post('/api/users', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'email',
            'created_at'
        ]);

        // Verify in database
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }
}
```

## Критерии качества тестов

### FIRST-принципы

| Принцип | Описание | Проверка |
|---------|----------|----------|
| **F**ast | Тесты должны выполняться быстро | < 100ms на unit-тест |
| **I**ndependent | Тесты не должны зависеть друг от друга | Можно запускать в любом порядке |
| **R**epeatable | Тесты дают одинаковый результат в любой среде | Нет зависимости от внешнего состояния |
| **S**elf-validating | Тесты имеют четкий pass/fail результат | Используются_assertions, а не print |
| **T**imely | Тесты пишутся вовремя (TDD предпочтителен) | Тесты существуют для нового кода |

### Покрытие кода

**Что проверять:**
- [ ] Покрытие branch coverage (все ветви условий)
- [ ] Покрытие edge cases (пустые значения, null, границы)
- [ ] Покрытие error paths (исключения, ошибки)
- [ ] Критический бизнес-код покрыт на 80%+

**Метрики:**
```
Line Coverage:     ≥ 80%
Branch Coverage:   ≥ 70%
Method Coverage:   ≥ 80%
```

### Качество тестового кода

**Naming:**
- [ ] Имена тестов описывают сценарий (testCreateUser_WhenEmailExists_ThrowsException)
- [ ] Используется единый стиль именования
- [ ] Имена читаются как требования (should_return_user_when_valid_data)
- [ ] **Все методы должны быть в camelCase** (testCreateUser, not test_create_user или TestCreateUser)

**Structure:**
- [ ] Следует паттерну Arrange-Act-Assert (AAA)
- [ ] Один тест — одна концепция
- [ ] Нет дублирования кода между тестами
- [ ] Используются setup/teardown методы

**Assertions:**
- [ ] Конкретные assertions (assertEquals вместо assertTrue)
- [ ] Проверяется только необходимое
- [ ] Есть сообщения об ошибках в assertions
- [ ] Избегается избыточных assertions

**Maintainability:**
- [ ] Тесты легко читать и понимать
- [ ] Минимум магических чисел (используются константы)
- [ ] Фикстуры вынесены отдельно
- [ ] Нет хрупких тестов (ломаются при рефакторинге)

## Mocking и Stubbing

### Когда использовать Mocks

**Используйте Mock для:**
- Проверки взаимодействия между объектами
- Тестирования что метод был вызван N раз
- Тестирования порядка вызовов

**Пример:**
```php
public function testNotifyUser_WhenOrderCreated(): void
{
    $emailMock = $this->createMock(EmailService::class);
    $emailMock->expects($this->once())
        ->method('send')
        ->with($this->equalTo('user@example.com'));

    $orderService = new OrderService($emailMock);
    $orderService->createOrder($userData);
}
```

### Когда использовать Stubs

**Используйте Stub для:**
- Предоставления тестовых данных
- Изоляции от внешних зависимостей
- Симуляции различных сценариев

**Пример:**
```php
public function testGetTotal_WithDiscount(): void
{
    $discountStub = $this->createStub(DiscountInterface::class);
    $discountStub->method('getPercent')->willReturn(10);

    $cart = new ShoppingCart($discountStub);
    $cart->addItem($item);

    $this->assertEquals(90, $cart->getTotal());
}
```

### Когда использовать Fakes

**Используйте Fake для:**
- Замены тяжелых зависимостей (БД, API)
- Ускорения тестов
- Тестирования в памяти

**Пример:**
```php
class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }
}
```

## Test Fixtures и Data Providers

### Fixtures

**Что проверять:**
- [ ] Фикстуры переиспользуемы
- [ ] Фикстуры читаемы
- [ ] Фикстуры изолированы (не зависят от других тестов)

**Пример:**
```php
protected function createUser(array $overrides = []): User
{
    $defaults = [
        'email' => 'test@example.com',
        'password' => 'password123',
        'name' => 'Test User'
    ];

    $data = array_merge($defaults, $overrides);
    return $this->userService->create($data);
}

public function testUpdateUser_EmailChanged(): void
{
    $user = $this->createUser();
    $this->userService->updateEmail($user, 'new@example.com');
    $this->assertEquals('new@example.com', $user->getEmail());
}
```

### Фабрики (Factories)

**Важное правило:** Для создания тестовых данных используйте **фабрики**, а не прямое создание через ORM/модели.

**Почему фабрики предпочтительнее ORM:**
- **Изоляция тестов**: Фабрики создают данные в памяти или через тестовые репозитории, не затрагивая реальную БД
- **Скорость**: Тесты выполняются быстрее без лишних запросов к БД
- **Гибкость**: Легко переопределять отдельные поля через `$overrides`
- **Явность**: Четко видно, какие данные создаются для теста
- **Безопасность**: Нет риска случайно изменить данные в production-подобной БД

**Когда использовать фабрики:**
- Unit-тесты сервисов, контроллеров, доменных сущностей
- Тесты, где не требуется реальная персистентность данных
- Быстрые изолированные тесты без зависимости от состояния БД

**Когда использовать ORM (исключения):**
- Integration-тесты repository слоя
- Functional-тесты с полной проверкой цикла CRUD
- Тесты миграций и seeders

**Пример фабрики:**
```php
class UserFactory
{
    public static function make(array $overrides = []): User
    {
        $defaults = [
            'id' => uniqid('user_', true),
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'name' => 'Test User',
            'status' => 'active',
        ];

        $data = array_merge($defaults, $overrides);
        return new User(
            id: $data['id'],
            email: $data['email'],
            password: $data['password'],
            name: $data['name'],
            status: $data['status'],
        );
    }

    public static function createMany(int $count, array $overrides = []): array
    {
        return array_map(
            fn($i) => self::make(array_merge($overrides, ['email' => "test{$i}@example.com"])),
            range(1, $count)
        );
    }
}

// Использование в тестах:
public function testProcessUsers_WithActiveUsers(): void
{
    // Arrange - создаём тестовые данные через фабрику
    $users = UserFactory::createMany(3, ['status' => 'active']);
    $processor = new UserProcessor();

    // Act
    $result = $processor->process($users);

    // Assert
    $this->assertCount(3, $result);
}
```

**Сравнение подходов:**

❌ **Плохо (ORM в unit-тестах):**
```php
public function testUserCalculation(): void
{
    // Медленно, требует БД, нарушает изоляцию
    $user = User::create(['email' => 'test@example.com']);
    $result = $user->calculateTotal();
    $this->assertEquals(100, $result);
}
```

✅ **Хорошо (Фабрика):**
```php
public function testUserCalculation(): void
{
    // Быстро, изолированно, нет зависимости от БД
    $user = UserFactory::make(['email' => 'test@example.com']);
    $result = $user->calculateTotal();
    $this->assertEquals(100, $result);
}
```

### Data Providers

**Когда использовать:**
- Тестирование с разными входными данными
- Тестирование граничных значений
- Параметризированные тесты

**Пример:**
```php
public static function invalidEmailProvider(): array
{
    return [
        [''],
        ['invalid'],
        ['@invalid.com'],
        ['user@'],
        [str_repeat('a', 255) . '@example.com'],
    ];
}

/**
 * @dataProvider invalidEmailProvider
 */
public function testCreateUser_InvalidEmail_ThrowsException(string $email): void
{
    $this->expectException(InvalidEmailException::class);
    $this->userService->create(['email' => $email]);
}
```

## Тестирование исключений

**Что проверять:**
- [ ] Исключения выбрасываются при неправильных данных
- [ ] Правильный тип исключения
- [ ] Правильное сообщение исключения
- [ ] Код исключения (если применимо)

**Пример:**
```php
public function testDeleteUser_NotFound_ThrowsException(): void
{
    $this->expectException(UserNotFoundException::class);
    $this->expectExceptionMessage('User with id 123 not found');

    $this->userService->delete('123');
}
```

## Асинхронное тестирование

**Что проверять:**
- [ ] Ожидание завершения асинхронных операций
- [ ] Тестирование callbacks/promises
- [ ] Тестирование очередей и workers

**Пример:**
```php
public function testProcessQueue_JobExecuted(): void
{
    $job = new ProcessEmailJob();
    
    $this->queue->push($job);
    $this->queue->process();

    $this->assertEmailSent();
}
```

## Чеклист проверки тестов

### Структура и организация
- [ ] Тестовые файлы в правильной директории (tests/Unit, tests/Integration)
- [ ] Имена тестовых файлов соответствуют тестируемым классам
- [ ] Namespace тестов соответствует структуре проекта
- [ ] Тесты сгруппированы по функциональности

### Покрытие
- [ ] Все публичные методы покрыты тестами
- [ ] Покрыты позитивные сценарии
- [ ] Покрыты негативные сценарии
- [ ] Покрыты граничные случаи
- [ ] Покрыты error paths

### Качество assertions
- [ ] Assertions конкретные и понятные
- [ ] Нет избыточных assertions
- [ ] Проверяются правильные значения
- [ ] Есть сообщения об ошибках

### Изоляция
- [ ] Тесты независимы друг от друга
- [ ] Нет общего состояния между тестами
- [ ] Mocks используются корректно
- [ ] Нет зависимости от внешнего окружения

### Производительность
- [ ] Тесты выполняются быстро
- [ ] Нет лишних операций в setUp
- [ ] БД очищается между тестами
- [ ] Нет sleep/wait без необходимости

### Читаемость
- [ ] Имена тестов описывают поведение
- [ ] Код следует AAA паттерну
- [ ] Нет магических чисел
- [ ] Комментарии только для сложной логики

## Формат вывода результатов

### При написании тестов:

1. **Анализ тестируемого кода:**
   - Список публичных методов для тестирования
   - Зависимости, которые нужно мокировать
   - Граничные случаи и edge cases

2. **Структура тестового файла:**
   ```
   ClassNameTest
   ├── setUp()
   ├── testMethod1_Success()
   ├── testMethod1_InvalidInput_ThrowsException()
   ├── testMethod2_EdgeCase()
   └── ...
   ```

3. **Сгенерированный тестовый код** с комментариями о покрытых сценариях

### При проверке тестов:

1. **Оценка покрытия:**
   - Процент покрытия кода
   - Непокрытые методы/ветви
   - Критические непокрытые участки

2. **Нарушения FIRST-принципов:**
   - Медленные тесты
   - Зависимые тесты
   - Хрупкие тесты

3. **Проблемы качества:**
   - Плохие имена тестов
   - Слабые assertions
   - Избыточный код

4. **Рекомендации:**
   - Приоритизированный список улучшений
   - Примеры рефакторинга
   - Дополнительные тесты для написания

## Примеры рекомендаций

**Плохо:**
```
Тест плохой. Добавьте больше assertions.
```

**Хорошо:**
```
Проблемы в тесте testCreateUser:

1. Нарушение FIRST - Independent:
   - Тест зависит от состояния БД от предыдущего теста
   - Решение: Добавьте cleanup в tearDown()

2. Слабые assertions:
   - Используется assertTrue($result) вместо конкретных проверок
   - Решение:
     $this->assertInstanceOf(User::class, $result);
     $this->assertEquals($email, $result->getEmail());

3. Не покрыт edge case:
   - Нет теста для случая с существующим email
   - Добавьте: testCreateUser_DuplicateEmail_ThrowsException()

4. Нарушение AAA паттерна:
   - Логика Arrange смешана с Act
   - Решение: Четко разделите секции комментариями
```

## Интеграция с CI/CD

**Что проверять:**
- [ ] Тесты запускаются в CI при каждом коммите
- [ ] Есть проверка coverage threshold
- [ ] Тесты блокируют merge при failure
- [ ] Есть кэширование зависимостей для скорости

**Пример GitHub Actions:**
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Tests
        run: composer test
      - name: Check Coverage
        run: phpunit --coverage-clover=coverage.xml
      - name: Upload Coverage
        uses: codecov/codecov-action@v3
```
