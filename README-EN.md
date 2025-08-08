[![SonarQube Cloud](https://sonarcloud.io/images/project_badges/sonarcloud-highlight.svg)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=vulnerabilities)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=bugs)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=sqale_index)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=coverage)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

---

# Serendipity

**The Hyperf missing component**

Serendipity is a PHP library that extends the Hyperf framework with advanced Domain-Driven Design (DDD) features,
intelligent validation, automatic serialization, and robust infrastructure for high-performance applications.

## 🍿 Overview

Serendipity fills the gaps in the Hyperf ecosystem by providing a powerful abstraction layer that combines the best
development patterns with Hyperf's asynchronous performance. Built on top
of [Constructo](https://github.com/devitools/constructo), it offers advanced metaprogramming to resolve dependencies and
format data flexibly.

### Key Features

- **🏗️ DDD Architecture**: Complete structure following Domain-Driven Design
- **⚡ Async by Default**: Fully compatible with Hyperf coroutines
- **🔍 Smart Validation**: Attribute-based validation system with intelligent rules
- **📊 Automatic Serialization**: Smart entity conversion to different formats
- **🎯 Type Safety**: Strong typing with generics support
- **🧪 Testability**: Complete tools for unit and integration testing
- **📈 Observability**: Structured logging and integrated monitoring

## 🚀 Installation

### Prerequisites

- PHP 8.3+
- Extensions: ds, json, mongodb, pdo, swoole
- Hyperf 3.1+
- Docker 25+ (for development)
- Docker Compose 2.23+

### Install via Composer

```bash
composer require devitools/serendipity
```

### Basic Configuration

**Register the ConfigProvider** in your `config/config.php`:

```php
<?php

return [
    'providers' => [
        Serendipity\ConfigProvider::class,
    ],
];
```

**Configure dependencies** in `config/autoload/dependencies.php`:

```php
<?php

return [
    \Constructo\Contract\Reflect\TypesFactory::class => 
        \Serendipity\Hyperf\Support\HyperfTypesFactory::class,
    \Constructo\Contract\Reflect\SpecsFactory::class => 
        \Serendipity\Hyperf\Support\HyperfSpecsFactory::class,
];
```

## 🎯 Core Features

### Strongly Typed Entities

Create robust entities with automatic validation and intelligent serialization:

```php
<?php

use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;
use Constructo\Type\Timestamp;

class Game extends GameCommand
{
    public function __construct(
        #[Managed('id')]
        public readonly string $id,
        #[Managed('timestamp')]
        public readonly Timestamp $createdAt,
        #[Managed('timestamp')]
        public readonly Timestamp $updatedAt,
        #[Pattern('/^[a-zA-Z]{1,255}$/')]
        string $name,
        #[Pattern]
        string $slug,
        Timestamp $publishedAt,
        array $data,
        FeatureCollection $features,
    ) {
        parent::__construct(
            name: $name,
            slug: $slug,
            publishedAt: $publishedAt,
            data: $data,
            features: $features,
        );
    }
}
```

### Typed Collections

Work with type-safe collections that ensure data integrity:

```php
<?php

use Constructo\Type\Collection;

/**
 * @extends Collection<Feature>
 */
class FeatureCollection extends Collection
{
    public function current(): Feature
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): Feature
    {
        return ($datum instanceof Feature)
            ? $datum
            : throw $this->exception(Feature::class, $datum);
    }
}
```

### Smart Input Validation

Integrated validation system with Hyperf that supports complex rules:

```php
<?php

use Serendipity\Presentation\Input;

final class HealthInput extends Input
{
    public function rules(): array
    {
        return [
            'message' => 'sometimes|string|max:255',
            'level' => 'required|in:debug,info,warning,error',
            'metadata' => 'array',
        ];
    }
}
```

### Actions with Dependency Injection

Create clean actions with automatic dependency injection:

```php
<?php

readonly class HealthAction
{
    public function __invoke(HealthInput $input): array
    {
        return [
            'method' => $input->getMethod(),
            'message' => $input->value('message', 'System running perfectly!'),
            'timestamp' => time(),
            'status' => 'healthy'
        ];
    }
}
```

## 🏗️ Project Architecture with Serendipity

Recommended structure for projects using Serendipity, based on real production projects:

```
project/
├── .github/               # GitHub workflows and templates
├── .project/              # Project-specific configurations
├── app/                   # Application source code
│   ├── Application/       # Application use cases
│   │   ├── Exception/     # Application exceptions
│   │   └── Service/       # Application services
│   ├── Domain/            # Pure business logic
│   │   ├── Entity/        # Domain entities
│   │   ├── Enum/          # Domain enums
│   │   ├── Provider/      # Domain providers
│   │   ├── Repository/    # Repository contracts
│   │   ├── Service/       # Domain services
│   │   ├── Support/       # Domain utilities
│   │   └── Validator/     # Business validators
│   ├── Infrastructure/    # Infrastructure implementations
│   │   ├── Exception/     # Infrastructure exceptions
│   │   ├── Parser/        # Data parsers
│   │   ├── Repository/    # Repository implementations
│   │   ├── Service/       # Infrastructure services
│   │   ├── Support/       # Infrastructure utilities
│   │   └── Validator/     # Infrastructure validators
│   └── Presentation/      # Presentation layer
│       ├── Action/        # Controllers/Actions
│       ├── Input/         # Input validation
│       └── Service/       # Presentation services
├── bin/                   # Executable scripts
│   ├── hyperf.php         # Main Hyperf script
│   └── phpunit.php        # Test script
├── compose.override.yml   # Docker Compose override
├── compose.yml           # Main Docker Compose configuration
├── composer.json         # Composer dependencies
├── composer.lock         # Dependencies lock file
├── config/               # Application configurations
│   └── autoload/         # Auto-loaded configurations
│       ├── commands.php
│       ├── databases.php
│       ├── dependencies.php
│       ├── exceptions.php
│       ├── http.php
│       ├── listeners.php
│       ├── logger.php
│       ├── middlewares.php
│       ├── schema.php
│       └── server.php
├── deptrac.yaml          # Dependency analysis configuration
├── Dockerfile            # Docker configuration
├── docs/                 # Project documentation
├── LICENSE               # Project license
├── makefile              # Development commands
├── migrations/           # Database migrations
├── phpcs.xml            # PHP CodeSniffer configuration
├── phpmd.xml            # PHP Mess Detector configuration
├── phpstan.neon         # PHPStan configuration
├── phpunit.xml          # PHPUnit configuration
├── psalm.xml            # Psalm configuration
├── README.md            # Main documentation
├── rector.php           # Rector configuration
├── runtime/             # Temporary files and cache
├── sonar-project.properties # SonarQube configuration
├── storage/             # Local storage
├── tests/               # Automated tests
│   ├── Application/     # Application tests
│   ├── Domain/          # Domain tests
│   ├── Infrastructure/  # Infrastructure tests
│   └── Presentation/    # Presentation tests
└── vendor/              # Composer dependencies
```

### Layer Organization

**Application Layer** - Use cases and orchestration

- **Service/**: Coordinate operations between domain and infrastructure
- **Exception/**: Application layer specific exceptions

**Domain Layer** - Pure business logic

- **Entity/**: Main business entities
- **Enum/**: Domain enumerations and constants
- **Repository/**: Persistence interfaces
- **Service/**: Complex business rules
- **Validator/**: Business rule validations

**Infrastructure Layer** - Technical implementations

- **Repository/**: Concrete repository implementations
- **Service/**: External API integrations
- **Parser/**: Data processing and transformation
- **Support/**: Technical utilities

**Presentation Layer** - External world interface

- **Action/**: HTTP endpoints and handlers
- **Input/**: Input validation and sanitization
- **Service/**: Response formatting

### Example Action Structure

```php
<?php

namespace App\Presentation\Action;

use App\Presentation\Input\ProcessLeadInput;
use App\Application\Service\LeadProcessorService;

readonly class ProcessLeadAction
{
    public function __construct(
        private LeadProcessorService $processor
    ) {}

    public function __invoke(ProcessLeadInput $input): array
    {
        $result = $this->processor->process($input->validated());
        
        return [
            'success' => true,
            'data' => $result->toArray(),
        ];
    }
}
```

## 📋 Practical Examples

### User Entity with Validation

```php
<?php

namespace App\Domain\Entity;

use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;
use DateTime;

readonly class User
{
    public function __construct(
        #[Managed('id')]
        public int $id,
        #[Pattern('/^[a-zA-Z\s]{2,100}$/')]
        public string $name,
        public DateTime $birthDate,
        public bool $isActive = true,
        public array $tags = [],
    ) {
    }

    public function getAge(): int
    {
        return $this->birthDate->diff(new DateTime())->y;
    }

    public function isAdult(): bool
    {
        return $this->getAge() >= 18;
    }

    public function addTag(string $tag): array
    {
        return [...$this->tags, $tag];
    }
}
```

### User Validation Input

```php
<?php

namespace App\Presentation\Input;

use Serendipity\Presentation\Input;

final class CreateUserInput extends Input
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s]+$/',
            'birth_date' => 'required|date|before:today',
            'is_active' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name must contain only letters and spaces',
            'birth_date.before' => 'Birth date must be before today',
            'email.unique' => 'This email is already in use',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
```

### User Creation Action

```php
<?php

namespace App\Presentation\Action;

use App\Domain\Entity\User;
use App\Presentation\Input\CreateUserInput;
use App\Domain\Service\UserService;
use DateTime;
use Psr\Log\LoggerInterface;

readonly class CreateUserAction
{
    public function __construct(
        private UserService $userService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CreateUserInput $input): array
    {
        $userData = $input->validated();
        
        $user = new User(
            id: 0, // Will be filled by database
            name: $userData['name'],
            birthDate: new DateTime($userData['birth_date']),
            isActive: $userData['is_active'] ?? true,
            tags: $userData['tags'] ?? []
        );

        $savedUser = $this->userService->create($user, $userData['password']);

        $this->logger->info('User created successfully', [
            'user_id' => $savedUser->id,
            'name' => $savedUser->name,
            'is_adult' => $savedUser->isAdult(),
        ]);

        return [
            'success' => true,
            'user' => [
                'id' => $savedUser->id,
                'name' => $savedUser->name,
                'age' => $savedUser->getAge(),
                'is_adult' => $savedUser->isAdult(),
                'is_active' => $savedUser->isActive,
                'tags' => $savedUser->tags,
            ],
        ];
    }
}
```

### User Domain Service

```php
<?php

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Service\PasswordHashService;

readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHashService $passwordService
    ) {}

    public function create(User $user, string $password): User
    {
        // Business validations
        if (!$user->isAdult()) {
            throw new \DomainException('User must be an adult');
        }

        if (count($user->tags) > 10) {
            throw new \DomainException('User cannot have more than 10 tags');
        }

        // Hash password
        $hashedPassword = $this->passwordService->hash($password);

        // Persist to database
        return $this->userRepository->save($user, $hashedPassword);
    }

    public function updateTags(int $userId, array $newTags): User
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \DomainException('User not found');
        }

        if (count($newTags) > 10) {
            throw new \DomainException('User cannot have more than 10 tags');
        }

        return $this->userRepository->updateTags($userId, $newTags);
    }
}
```

### User Repository

```php
<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user, string $hashedPassword): User;
    
    public function findById(int $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function updateTags(int $userId, array $tags): User;
    
    public function findActiveUsers(): array;
    
    public function findUsersByTag(string $tag): array;
}
```

### Repository Implementation

```php
<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Hyperf\Database\ConnectionInterface;
use DateTime;

readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $connection
    ) {}

    public function save(User $user, string $hashedPassword): User
    {
        $id = $this->connection->table('users')->insertGetId([
            'name' => $user->name,
            'email' => $user->email ?? '',
            'password' => $hashedPassword,
            'birth_date' => $user->birthDate->format('Y-m-d'),
            'is_active' => $user->isActive,
            'tags' => json_encode($user->tags),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new User(
            id: $id,
            name: $user->name,
            birthDate: $user->birthDate,
            isActive: $user->isActive,
            tags: $user->tags
        );
    }

    public function findById(int $id): ?User
    {
        $userData = $this->connection
            ->table('users')
            ->where('id', $id)
            ->first();

        if (!$userData) {
            return null;
        }

        return new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: (bool) $userData->is_active,
            tags: json_decode($userData->tags, true) ?? []
        );
    }

    public function findByEmail(string $email): ?User
    {
        $userData = $this->connection
            ->table('users')
            ->where('email', $email)
            ->first();

        if (!$userData) {
            return null;
        }

        return new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: (bool) $userData->is_active,
            tags: json_decode($userData->tags, true) ?? []
        );
    }

    public function updateTags(int $userId, array $tags): User
    {
        $this->connection
            ->table('users')
            ->where('id', $userId)
            ->update([
                'tags' => json_encode($tags),
                'updated_at' => now(),
            ]);

        return $this->findById($userId);
    }

    public function findActiveUsers(): array
    {
        $users = $this->connection
            ->table('users')
            ->where('is_active', true)
            ->get();

        return $users->map(fn($userData) => new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: true,
            tags: json_decode($userData->tags, true) ?? []
        ))->toArray();
    }

    public function findUsersByTag(string $tag): array
    {
        $users = $this->connection
            ->table('users')
            ->whereJsonContains('tags', $tag)
            ->get();

        return $users->map(fn($userData) => new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: (bool) $userData->is_active,
            tags: json_decode($userData->tags, true) ?? []
        ))->toArray();
    }
}
```

### Typed User Collection

```php
<?php

namespace App\Domain\Collection;

use Constructo\Type\Collection;
use App\Domain\Entity\User;

/**
 * @extends Collection<User>
 */
class UserCollection extends Collection
{
    public function current(): User
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): User
    {
        return ($datum instanceof User)
            ? $datum
            : throw $this->exception(User::class, $datum);
    }

    public function getActiveUsers(): UserCollection
    {
        return new self(
            array_filter($this->items, fn(User $user) => $user->isActive)
        );
    }

    public function getAdultUsers(): UserCollection
    {
        return new self(
            array_filter($this->items, fn(User $user) => $user->isAdult())
        );
    }

    public function getUsersByTag(string $tag): UserCollection
    {
        return new self(
            array_filter($this->items, fn(User $user) => in_array($tag, $user->tags))
        );
    }

    public function getAverageAge(): float
    {
        if ($this->count() === 0) {
            return 0;
        }

        $totalAge = array_sum(
            array_map(fn(User $user) => $user->getAge(), $this->items)
        );

        return $totalAge / $this->count();
    }
}
```

## 🧪 Testing

Serendipity provides robust testing tools:

```php
<?php

use Serendipity\Testing\TestCase;
use App\Domain\Entity\User;
use App\Presentation\Input\CreateUserInput;
use App\Presentation\Action\CreateUserAction;
use DateTime;

class CreateUserActionTest extends TestCase
{
    public function testCreateUserSuccess(): void
    {
        $input = new CreateUserInput([
            'name' => 'John Silva',
            'birth_date' => '1990-05-15',
            'email' => 'john@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
            'is_active' => true,
            'tags' => ['developer', 'php'],
        ]);

        $action = $this->container()->get(CreateUserAction::class);
        $result = $action($input);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('John Silva', $result['user']['name']);
        $this->assertTrue($result['user']['is_adult']);
        $this->assertTrue($result['user']['is_active']);
        $this->assertContains('developer', $result['user']['tags']);
    }

    public function testCreateUserValidationFails(): void
    {
        $this->expectException(\Hyperf\Validation\ValidationException::class);

        $input = new CreateUserInput([
            'name' => '', // Empty name
            'birth_date' => '2020-01-01', // Minor
            'email' => 'invalid-email', // Invalid email
            'password' => '123', // Too short password
        ]);

        $input->validated();
    }

    public function testUserEntityMethods(): void
    {
        $user = new User(
            id: 1,
            name: 'Maria Santos',
            birthDate: new DateTime('1985-03-20'),
            isActive: true,
            tags: ['designer', 'ui-ux']
        );

        $this->assertEquals(39, $user->getAge()); // Assuming 2024
        $this->assertTrue($user->isAdult());
        $this->assertEquals(['designer', 'ui-ux', 'frontend'], $user->addTag('frontend'));
    }
}

class UserServiceTest extends TestCase
{
    public function testCreateUserWithBusinessRules(): void
    {
        $userService = $this->container()->get(\App\Domain\Service\UserService::class);
        
        $user = new User(
            id: 0,
            name: 'Peter Costa',
            birthDate: new DateTime('1992-08-10'),
            isActive: true,
            tags: ['backend']
        );

        $result = $userService->create($user, 'securePassword123');

        $this->assertInstanceOf(User::class, $result);
        $this->assertGreaterThan(0, $result->id);
    }

    public function testCreateMinorUserFails(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User must be an adult');

        $userService = $this->container()->get(\App\Domain\Service\UserService::class);
        
        $minorUser = new User(
            id: 0,
            name: 'Child',
            birthDate: new DateTime('2020-01-01'),
            isActive: true,
            tags: []
        );

        $userService->create($minorUser, 'password123');
    }

    public function testUpdateTagsSuccess(): void
    {
        $userService = $this->container()->get(\App\Domain\Service\UserService::class);
        
        // Mock existing user
        $existingUser = new User(
            id: 1,
            name: 'Ana Silva',
            birthDate: new DateTime('1988-12-05'),
            isActive: true,
            tags: ['old-tag']
        );

        $newTags = ['new-tag', 'another-tag'];
        $result = $userService->updateTags(1, $newTags);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($newTags, $result->tags);
    }
}

class UserCollectionTest extends TestCase
{
    public function testUserCollectionFilters(): void
    {
        $users = [
            new User(1, 'John', new DateTime('1990-01-01'), true, ['php']),
            new User(2, 'Maria', new DateTime('2010-01-01'), true, ['js']), // Minor
            new User(3, 'Peter', new DateTime('1985-01-01'), false, ['python']), // Inactive
            new User(4, 'Ana', new DateTime('1992-01-01'), true, ['php', 'laravel']),
        ];

        $collection = new \App\Domain\Collection\UserCollection($users);

        // Test active users filter
        $activeUsers = $collection->getActiveUsers();
        $this->assertCount(3, $activeUsers);

        // Test adult users filter
        $adultUsers = $collection->getAdultUsers();
        $this->assertCount(3, $adultUsers);

        // Test filter by tag
        $phpUsers = $collection->getUsersByTag('php');
        $this->assertCount(2, $phpUsers);

        // Test average age
        $averageAge = $collection->getAverageAge();
        $this->assertGreaterThan(0, $averageAge);
    }

    public function testEmptyCollectionAverageAge(): void
    {
        $collection = new \App\Domain\Collection\UserCollection([]);
        $this->assertEquals(0, $collection->getAverageAge());
    }
}
```

## ⚡ Performance and Observability

### Structured Logging

```php
<?php

$this->logger->info('Lead processed successfully', [
    'lead_id' => $leadId,
    'source' => $source,
    'processing_time_ms' => $processingTime,
    'memory_usage' => memory_get_usage(true),
]);
```

### Metrics and Monitoring

```php
<?php

// Integration with metrics systems
use Hyperf\Context\Context;

Context::set('metrics.processing_start', microtime(true));
$result = $this->processLead($input);
$duration = microtime(true) - Context::get('metrics.processing_start');

$this->logger->info('Performance metric', [
    'operation' => 'process_lead',
    'duration_ms' => round($duration * 1000, 2),
    'success' => $result->isSuccess(),
]);
```

## 🔧 Advanced Configuration

### Schema and Specifications

Configure custom schemas in `config/autoload/schema.php`:

```php
<?php

return [
    'specs' => [
        'lead' => [
            'id' => 'string',
            'name' => 'string',
            'email' => 'email',
            'phone' => 'string',
            'created_at' => 'timestamp',
        ],
        'quote' => [
            'id' => 'string',
            'lead_id' => 'string',
            'amount' => 'decimal',
            'status' => 'enum:pending,approved,rejected',
        ],
    ],
];
```

### Custom Middlewares

```php
<?php

use Serendipity\Hyperf\Middleware\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LeadValidationMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Lead-specific validation
        $body = $request->getParsedBody();
        
        if (isset($body['email']) && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
        
        return $handler->handle($request);
    }
}
```

## 📚 CLI Commands

Serendipity includes useful commands for development:

```bash
# Generate validation rules
php bin/hyperf.php gen:rules LeadRules

# Execute health check via CLI
php bin/hyperf.php health:check

# Process leads in batch
php bin/hyperf.php lead:process-batch

# Clear caches
php bin/hyperf.php cache:clear
```

## 🤝 Contributing

Fork the project, create a feature branch, commit your changes, push to the branch and open a Pull Request.

### Development Standards

- Follow PSR-12 for PHP code
- Use strong typing whenever possible
- Implement tests for new features
- Document changes in README

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Related Links

- [Hyperf Framework](https://hyperf.io/)
- [Constructo](https://github.com/devitools/constructo)
- [Devitools](https://devi.tools/)

---

**Serendipity** - Discovering Hyperf's full potential through elegant and powerful components.
