# Serendipity Quick Start Guide

This guide will help you get the Serendipity project up and running quickly on your local development environment.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP 8.3 or higher**
- **Docker and Docker Compose**
- **Composer** (PHP dependency manager)
- **Git** (for version control)

## Quick Setup (5 Minutes)

### 1. Clone the Repository

```bash
git clone <repository-url>
cd serendipity
```

### 2. Environment Configuration

Create your environment file from the example:

```bash
cp .env.example .env
```

Edit the `.env` file to configure your local settings (database credentials, etc.).

### 3. One-Command Setup

Run the complete setup with a single command:

```bash
make setup
```

This command will:
- Prune any existing containers
- Install PHP dependencies via Composer
- Start all Docker containers
- Run database migrations
- Initialize the MongoDB replica set

### 4. Verify Installation

Check that all services are running:

```bash
docker-compose ps
```

You should see the following services running:
- `app` (Hyperf PHP 8.3 application)
- `postgres` (PostgreSQL 16.2 database)
- `mongo` (MongoDB 6.0)
- `mongo-bootstrap` (MongoDB initialization)

## Essential Commands

### Daily Development Commands

```bash
# Start the project
make up

# Stop the project
make down

# Access the application container
make bash

# View logs
docker-compose logs -f app

# Restart services
make restart
```

### Development Workflow

```bash
# Install/update dependencies
make install

# Run database migrations
make migrate

# Run tests with coverage
make test

# Check code style
make lint

# Fix code style issues
make fix

# Run complete CI pipeline
make ci
```

## Project Structure Overview

```
serendipity/
├── .ai/                    # AI context and documentation
├── .junie/                 # Legacy guidelines (kept for reference)
├── bin/                    # Command-line scripts
├── config/                 # Application configuration
├── migrations/             # Database migrations
├── src/                    # Source code
│   ├── Domain/            # Domain layer (entities, services, repositories)
│   ├── Infrastructure/    # Infrastructure layer (database, external services)
│   └── Application/       # Application layer (use cases, DTOs)
├── tests/                 # Test files
├── vendor/                # Composer dependencies
├── compose.yml            # Docker Compose configuration
├── Makefile              # Development commands
└── README.md             # Project documentation
```

## First Steps After Setup

### 1. Explore the Codebase

Start by examining the main directories:

```bash
# Look at the domain layer
ls -la src/Domain/

# Check available tests
ls -la tests/

# Review configuration
ls -la config/
```

### 2. Run Your First Test

```bash
# Run all tests
make test

# Run a specific test
docker-compose exec app composer test -- --filter=ExampleTest
```

### 3. Check Code Quality

```bash
# Run all linting tools
make lint

# Check specific tools
make lint.phpstan    # Static analysis
make lint.phpcs      # Code style
make lint.psalm      # Additional static analysis
```

## Development Environment

### Docker Services

The project uses Docker Compose with these services:

- **app**: Main Hyperf application container
- **postgres**: PostgreSQL database for relational data
- **mongo**: MongoDB for document storage
- **mongo-bootstrap**: Initializes MongoDB replica set

### Database Access

#### PostgreSQL
- **Host**: localhost
- **Port**: 5432
- **Database**: serendipity
- **Username/Password**: Check your `.env` file

#### MongoDB
- **Host**: localhost
- **Port**: 27017
- **Database**: serendipity
- **Replica Set**: rs0

### Application Access

Once running, the application will be available at:
- **HTTP**: http://localhost:9501
- **WebSocket**: ws://localhost:9502 (if enabled)

## Common Tasks

### Adding New Features

1. **Create domain entities** in `src/Domain/Entity/`
2. **Define repositories** in `src/Domain/Repository/`
3. **Implement services** in `src/Application/Service/`
4. **Add infrastructure** in `src/Infrastructure/`
5. **Write tests** in `tests/`

### Database Operations

```bash
# Create a new migration
docker-compose exec app php bin/hyperf.php gen:migration CreateUsersTable

# Run migrations
make migrate

# Rollback migrations
docker-compose exec app php bin/hyperf.php migrate:rollback
```

### Testing

```bash
# Run all tests
make test

# Run tests with specific filter
docker-compose exec app composer test -- --filter=UserTest

# Generate coverage report
docker-compose exec app composer test -- --coverage-html=tests/coverage
```

## Troubleshooting

### Common Issues

#### Port Conflicts
If you get port conflicts, check what's running on the required ports:
```bash
# Check port usage
lsof -i :5432  # PostgreSQL
lsof -i :27017 # MongoDB
lsof -i :9501  # Application
```

#### Permission Issues
If you encounter permission issues with Docker:
```bash
# Fix permissions
sudo chown -R $USER:$USER .
```

#### Database Connection Issues
If the application can't connect to databases:
```bash
# Restart database services
docker-compose restart postgres mongo

# Check service logs
docker-compose logs postgres
docker-compose logs mongo
```

### Getting Help

1. **Check the logs**: `docker-compose logs -f app`
2. **Verify services**: `docker-compose ps`
3. **Review configuration**: Check your `.env` file
4. **Run diagnostics**: `make ci` to run all checks

## Next Steps

After completing the quick start:

1. **Read the full documentation** in the `.ai/` directory:
   - `context.md` - Project overview and architecture
   - `guidelines.md` - Development guidelines and best practices
   - `patterns.md` - Design patterns used in the project
   - `decisions.md` - Architectural decisions and rationale

2. **Explore the codebase** to understand the project structure

3. **Run the test suite** to ensure everything is working correctly

4. **Start developing** your first feature following the established patterns

## Useful Resources

- **Hyperf Documentation**: https://hyperf.wiki/
- **Hyperf Context Reference**: https://context7.com/hyperf/hyperf/llms.txt
- **PHP 8.3 Documentation**: https://www.php.net/manual/en/
- **Docker Compose Reference**: https://docs.docker.com/compose/

## Make Commands Reference

```bash
make help           # Show all available commands
make setup          # Complete project setup
make up             # Start services
make down           # Stop services
make restart        # Restart services
make bash           # Access application container
make install        # Install dependencies
make migrate        # Run database migrations
make test           # Run tests with coverage
make lint           # Run all linting tools
make fix            # Fix code style issues
make ci             # Run complete CI pipeline
make clean          # Clean up containers and volumes
```

Happy coding! 🚀
