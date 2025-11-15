# Serendipity Development Guidelines

This document provides essential guidelines for developers working on the Serendipity project, covering coding standards, testing practices, and development workflows.

## Code Quality and Style Guidelines

### Code Style Standards

The project uses PHP-CS-Fixer with a combination of PSR2, Symfony, DoctrineAnnotation, and PhpCsFixer rule sets. Key style rules include:

- **Short array syntax**: Use `[]` instead of `array()`
- **String quotes**: Use single quotes for strings
- **Concatenation**: Single space around concatenation operators
- **Type declarations**: Always use strict type declarations
- **Import ordering**: Ordered imports (classes, functions, constants)

#### Code Style Commands

```bash
# Check code style
make lint.phpcs

# Fix code style issues
make fix
```

### Static Analysis Guidelines

The project uses multiple static analysis tools to maintain code quality:

```bash
# Run PHPStan
make lint.phpstan

# Run Psalm
make lint.psalm

# Run Deptrac (dependency analysis)
make lint.deptrac

# Run PHP Mess Detector
make lint.phpmd

# Run Rector (with dry-run)
make lint.rector

# Run all linting tools
make lint
```

### Continuous Integration

Always run the CI command before committing:

```bash
make ci
```

## Testing Guidelines

### Test Structure and Naming

- **File naming**: Use `*Test.php` for test files
- **Method naming**: Use `test*` prefix for test methods with descriptive names
- **Class structure**: Extend `PHPUnit\Framework\TestCase` for unit tests
- **Namespace**: Follow the existing namespace structure in the `tests` directory

### Test Writing Standards

- **Pattern**: Always follow the Arrange-Act-Assert (AAA) pattern
- **Descriptive names**: Use descriptive test method names that explain what is being tested
- **Coverage**: Always try to reach 100% code coverage
- **No reflection**: Do not use reflection to change method visibility
- **No annotations**: Avoid PHPUnit annotations like `@internal`, `@nocoverage`

### Test Execution

```bash
# Run all tests with coverage report
docker-compose exec app composer test

# Run specific test file
docker-compose exec app composer test -- --filter=ExampleTest
```

### Coverage Analysis

Use the coverage results to ensure comprehensive testing:
- `tests/.phpunit/text.txt`
- `tests/.phpunit/clover.xml`
- `tests/.phpunit/logging.xml`

### Example Test Structure

```php
<?php

declare(strict_types=1);

namespace Serendipity\Test\Example;

use PHPUnit\Framework\TestCase;

final class SimpleTest extends TestCase
{
    public function testSimpleAssertion(): void
    {
        // Arrange
        $expected = true;

        // Act
        $actual = true;

        // Assert
        $this->assertEquals($expected, $actual);
    }
}
```

## Coroutine-Based Development Guidelines

### Understanding Coroutines

This project uses Hyperf's coroutine-based architecture, which requires special consideration:

1. **Custom PHPUnit wrapper**: Uses Swoole's coroutine runtime (`bin/phpunit.php`)
2. **Coroutine environment**: Tests execute in a coroutine environment
3. **Async considerations**: Be aware of potential race conditions and deadlocks
4. **Bootstrap process**: Hyperf container and coroutines are initialized automatically

### Coroutine Best Practices

- Design all components to work efficiently in a coroutine environment
- Use dependency injection for loose coupling
- Leverage aspect-oriented programming for cross-cutting concerns
- Optimize for high performance and resource efficiency

## Development Workflow Guidelines

### Environment Setup

1. **Prerequisites**: Ensure PHP 8.3+, Docker, Docker Compose, and Composer are installed
2. **Environment file**: Create `.env` from `.env.example`
3. **Configuration**: Configure environment variables according to your needs

### Daily Development Commands

```bash
# Complete setup (prune, install dependencies, start containers, run migrations)
make setup

# Start the project
make up

# Stop the project
make down

# Access the application container
make bash

# Install dependencies
make install

# Run database migrations
make migrate

# Show all available commands
make help
```

## Code Organization Guidelines

### Project Structure

- `bin/`: Command-line scripts
- `config/`: Application configuration
- `migrations/`: Database migrations
- `src/`: Source code
- `tests/`: Test files
- `vendor/`: Dependencies (managed by Composer)

### Namespace Guidelines

- Follow PSR-4 autoloading standards
- Use meaningful namespace hierarchies
- Keep related classes in appropriate namespaces
- Maintain consistency with existing namespace structure

## Database Guidelines

### PostgreSQL Usage

- Use for relational data requiring ACID compliance
- Leverage complex queries and joins
- Implement proper indexing strategies
- Use migrations for schema changes

### MongoDB Usage

- Use for document storage and flexible schemas
- Leverage replica set configuration for high availability
- Use for data requiring horizontal scaling
- Implement proper document design patterns

## Error Handling Guidelines

### Exception Management

- Use custom exception handlers for consistent error responses
- Integrate with Sentry for error tracking
- Provide appropriate error context without exposing sensitive information
- Implement proper logging for debugging

### Logging Standards

- Use Monolog for all application logging
- Configure appropriate log levels for different environments
- In development, output logs to stdout
- Structure log messages for easy parsing and analysis

## Performance Guidelines

### Optimization Principles

- Design with coroutine-first approach
- Minimize blocking operations
- Use connection pooling for database operations
- Implement proper caching strategies
- Monitor performance metrics regularly

### Resource Management

- Properly manage database connections
- Use appropriate memory management techniques
- Implement efficient data structures
- Monitor resource usage in production
