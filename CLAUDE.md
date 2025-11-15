# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Serendipity is a PHP library that extends the Hyperf framework with Domain-Driven Design (DDD) patterns, intelligent validation, automatic serialization, and robust infrastructure for high-performance asynchronous applications. It uses the [Constructo](https://github.com/devitools/constructo) library as its foundation for advanced metaprogramming.

**Technology Stack:**
- PHP 8.3+ with Hyperf 3.1 framework
- Swoole for coroutine-based async execution
- PostgreSQL 16.2 for relational data
- MongoDB 6.0 for document storage
- Docker Compose for development environment

## Development Commands

### Environment Setup
```bash
# Complete project setup (one-time)
make setup

# Start services
make up

# Stop services
make down

# Access application container
make bash
```

### Testing
```bash
# Run all tests with coverage
make test

# Run specific test
docker-compose exec app composer test -- --filter=YourTest

# Tests use custom PHPUnit wrapper (bin/phpunit.php) that runs in Swoole coroutine context
```

### Code Quality
```bash
# Fix all code style issues
make fix

# Run all linting tools (phpcs, phpstan, deptrac, phpmd, rector, psalm)
make lint

# Run individual linters
make lint.phpstan    # Static analysis
make lint.phpcs      # Code style
make lint.deptrac    # Architecture layer violations
make lint.phpmd      # Mess detector
make lint.psalm      # Additional static analysis

# Run complete CI pipeline
make ci
```

### Database Operations
```bash
# Run migrations
make migrate

# Generate new migration
docker-compose exec app php bin/hyperf.php gen:migration MigrationName
```

## Architecture Overview

Serendipity enforces a strict **4-layer architecture** with dependency rules validated by Deptrac:

### Layer Structure
1. **Domain Layer** (`src/Domain/`)
   - Pure business logic with no external dependencies
   - Can only depend on: Contract, Native PHP
   - Contains: Entities, Collections, Repository Interfaces, Domain Events, Exceptions

2. **Application Layer** (`src/Application/`)
   - Orchestrates domain operations
   - Can only depend on: Domain, Contract, Native PHP
   - Contains: Services, Exception handlers

3. **Infrastructure Layer** (`src/Infrastructure/`)
   - Technical implementations and external integrations
   - Can only depend on: Domain, Contract, Native PHP, Vendor packages
   - Contains: Repository implementations, Database adapters, HTTP clients, Loggers
   - Implements serialization/deserialization for MongoDB, PostgreSQL, HTTP

4. **Presentation Layer** (`src/Presentation/`)
   - Handles external interactions (HTTP, CLI, etc.)
   - Can depend on: All other layers
   - Contains: Actions (controllers), Input validators, Output formatters

### CQRS Pattern
Serendipity uses **Command-Query Responsibility Segregation**:
- **Commands** (writes): Use `*CommandRepository` interfaces
- **Queries** (reads): Use `*QueryRepository` interfaces
- Entities extend Command classes which extend the base `Entity` class

## Core Patterns and Conventions

### Entity Pattern
Entities use metadata attributes and inherit from Command classes:

```php
use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;

class Example extends ExampleCommand
{
    public function __construct(
        #[Managed('id')]
        public readonly string $id,
        #[Managed('timestamp')]
        public readonly Timestamp $createdAt,
        #[Pattern('/^[a-zA-Z]{1,255}$/')]
        string $name,
        // ... other fields
    ) {
        parent::__construct(/* pass non-managed fields to command */);
    }
}
```

**Key Points:**
- `#[Managed]` attributes track metadata fields (id, timestamps)
- `#[Pattern]` attributes define validation patterns
- Entities are immutable (readonly properties)
- Extend from Command classes to separate write responsibilities

### Collection Pattern
Type-safe collections with runtime validation:

```php
/**
 * @extends Collection<Example>
 */
final class ExampleCollection extends Collection
{
    public function current(): Example
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): Example
    {
        return ($datum instanceof Example)
            ? $datum
            : throw $this->exception(Example::class, $datum);
    }
}
```

### Input Validation Pattern
Input classes handle request validation:

```php
use Serendipity\Presentation\Input;

class CreateExampleInput extends Input
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'features' => 'required|array',
            'features.*.name' => 'required|string',
        ];
    }
}
```

**Usage Notes:**
- Use `->values()` to get all validated data
- Use `->value('key', $default)` to get specific values with type-safe defaults
- Default value type must match expected value type: `->value('id', '')` or `->value('count', 0)`
- AVOID `get()` or `has()` methods as they bypass type safety

### Action Pattern
Actions are readonly classes with `__invoke` method:

```php
readonly class CreateExampleAction
{
    public function __construct(
        private Builder $builder,
        private ExampleCommandRepository $repository,
    ) {}

    public function __invoke(CreateExampleInput $input): Message
    {
        $command = $this->builder->build(ExampleCommand::class, $input->values());
        $id = $this->repository->create($command);
        return Accepted::createFrom($id);
    }
}
```

**Key Points:**
- Use constructor dependency injection
- Return `Message` types (Ok, Accepted, NotFound, etc.)
- Separate actions for commands and queries
- Use `Builder` for entity construction from arrays

### Repository Pattern
Separate interfaces for commands and queries:

```php
// Command Repository (writes)
interface ExampleCommandRepository
{
    public function create(ExampleCommand $entity): string;
    public function delete(string $id): bool;
}

// Query Repository (reads)
interface ExampleQueryRepository
{
    public function findById(string $id): ?Example;
    public function findAll(): ExampleCollection;
}
```

## Coding Standards

### General Rules
- **Strict typing**: Always use `declare(strict_types=1);` at the top of files
- **Readonly**: Prefer readonly classes and properties for immutability
- **Type hints**: Always use parameter and return type hints
- **Array syntax**: Use `[]` not `array()`
- **Quotes**: Use single quotes for strings
- **Imports**: Keep ordered (classes, functions, constants)

### Testing Standards
- Follow Arrange-Act-Assert (AAA) pattern
- Test files must end with `Test.php`
- Test methods must start with `test`
- Extend `PHPUnit\Framework\TestCase`
- Target 100% code coverage
- Tests run in Swoole coroutine context via `bin/phpunit.php`

### Coroutine Awareness
This is a **coroutine-based** application using Swoole:
- All code runs in async coroutine context
- Avoid blocking operations
- Use connection pooling for databases
- Be aware of potential race conditions
- Leverage Hyperf's coroutine-safe dependency injection

## Key Files and Configurations

### Quality Tools Configuration
- `.php-cs-fixer.php` - Code style rules (PSR-12 + custom rules)
- `phpstan.neon` - Static analysis configuration
- `psalm.xml` - Additional static analysis
- `deptrac.yaml` - Architecture layer dependency rules
- `phpunit.xml` - Test configuration with coverage settings
- `rector.php` - Automated refactoring rules
- `phpmd.xml` - Code quality rules

### Important Directories
- `src/_/` - Helper functions (`mirror.php`, `runtime.php`)
- `src/Example/` - Reference implementations (use as patterns)
- `src/Testing/` - Testing utilities and base classes
- `bin/phpunit.php` - Custom PHPUnit wrapper for Swoole coroutines
- `config/autoload/` - Auto-loaded Hyperf configurations

### Database Adapters
- `Infrastructure/Repository/PostgresRepository.php` - Relational DB base
- `Infrastructure/Repository/MongoRepository.php` - Document DB base
- `Infrastructure/Repository/HttpRepository.php` - HTTP client base
- `Infrastructure/Repository/SleekDBRepository.php` - File-based DB

### Serialization System
The library includes advanced serialization/deserialization:
- `Infrastructure/Adapter/Serializer.php` - Entity to array/JSON
- `Infrastructure/Adapter/Deserializer.php` - Array/JSON to entity
- Repository-specific factories for MongoDB and PostgreSQL formatters
- Handles timestamps, dates, managed fields, and nested collections

## Additional Resources

For detailed patterns and examples, refer to:
- `.ai/patterns.md` - Complete pattern documentation with examples
- `.ai/guidelines.md` - Development standards and best practices
- `.ai/quickstart.md` - Setup and common tasks
- `.ai/context.md` - Project context and technology choices
- `.ai/decisions.md` - Architectural decision records
- `src/Example/` - Reference implementations
- README.md - User-facing library documentation

## Hyperf Framework Context

When you need Hyperf-specific documentation, refer to:
- https://hyperf.wiki/ - Official Hyperf documentation
- https://context7.com/hyperf/hyperf/llms.txt - Hyperf LLM context
