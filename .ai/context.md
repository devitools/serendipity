# Serendipity Project Context

## Project Overview

Serendipity is a high-performance PHP application built on the Hyperf framework, leveraging coroutine-based concurrency
for optimal performance. The project combines modern PHP development practices with advanced architectural patterns.

## Technology Stack

### Core Framework

- **Hyperf Framework**: A high-performance, coroutine-based PHP framework
- **PHP 8.3+**: Latest PHP version with modern language features
- **Swoole**: Coroutine runtime for high-performance networking

### Key Features

- Dependency injection container
- Aspect-oriented programming
- Coroutine-based concurrency
- High-performance HTTP server

### Database Systems

- **PostgreSQL 16.2**: Primary relational database for structured data
- **MongoDB 6.0**: Document database with replica set configuration for distributed data storage

### Development Environment

- **Docker & Docker Compose**: Containerized development environment
- **Composer**: PHP dependency management

## Project Structure

```
serendipity/
├── bin/                 # Command-line scripts
├── config/             # Application configuration
├── migrations/         # Database migrations
├── src/               # Source code
├── tests/             # Test files
└── vendor/            # Dependencies (managed by Composer)
```

## External References

### Hyperf Framework Context

For comprehensive information about the Hyperf framework and its capabilities, refer to:

- **Hyperf LLM Context**: https://context7.com/hyperf/hyperf/llms.txt

This resource provides detailed context about Hyperf's architecture, features, and best practices that are directly
applicable to the Serendipity project.

## Architecture Principles

The project follows several key architectural principles:

1. **Coroutine-First Design**: All components are designed to work efficiently in a coroutine environment
2. **Dependency Injection**: Heavy use of DI container for loose coupling
3. **Aspect-Oriented Programming**: Cross-cutting concerns handled through AOP
4. **High Performance**: Optimized for speed and resource efficiency
5. **Modern PHP Practices**: Leveraging PHP 8.3+ features and strict typing

## Development Philosophy

- **Code Quality**: Emphasis on maintainable, testable code
- **Performance**: Coroutine-based architecture for high concurrency
- **Testing**: Comprehensive test coverage with coroutine-aware testing
- **Documentation**: Clear documentation and guidelines for developers

## Important Notes

Always use as the first task to reload the context and the last task to save the context.
