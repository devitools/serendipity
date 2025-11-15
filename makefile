#!/usr/bin/make

.DEFAULT_GOAL := help

COMPOSE_RUNNER ?= "docker-compose"


setup: ## Setup the project
	@make prune
	@make install
	@make up
	@make wait-for-postgres
	@make migrate

##@ Bash controls

bash: ## Start nginx bash
	@make up
	@$(COMPOSE_RUNNER) exec app sh

up: ## Start the project
	@$(COMPOSE_RUNNER) --profile mongo --profile postgres up -d

down: ## Stop the project
	@$(COMPOSE_RUNNER) --profile mongo --profile postgres down --remove-orphans

prune: ## Prune the project
	@$(COMPOSE_RUNNER) --profile mongo --profile postgres down --remove-orphans --volumes

watch: ## Start the project in watch mode
	@make up
	@$(COMPOSE_RUNNER) logs -f app


##@ Composer

install: ## Composer install dependencies
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app install

dump: ## Run the composer dump
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app dump-autoload

##@ Code analysis

lint: ## Perform code style lint
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint

lint.phpcs: ## Perform code style list using phpcs
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint:phpcs

lint.phpstan: ## Perform code style list using phpstan
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint:phpstan

lint.deptrac: ## Perform code style list using deptrac
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint:deptrac

lint.phpmd: ## Perform code style list using phpmd
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint:phpmd

lint.rector: ## Perform code style list using rector
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint:rector

lint.psalm: ## Perform code style list using psalm
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app lint:psalm

fix: ## Perform code style fix
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app fix

##@ Tests

test: ## Execute suite's test unit and integration
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app test -- --coverage-html tests/.phpunit/html


##@ CI

ci: ## Execute all analysis as CI does
	@$(COMPOSE_RUNNER) run --rm --entrypoint composer app ci


##@ Database

postgres: ## Start the postgres container
	@$(COMPOSE_RUNNER) up -d

migrate: ## Execute the migrations
	@$(COMPOSE_RUNNER) run --rm --entrypoint "php bin/hyperf.php" app migrate --database=postgres

wait-for-postgres: ## Wait for Postgres to be ready
	@echo "Waiting for Postgres to be ready..."
	@for i in {1..12}; do \
		if $(COMPOSE_RUNNER) exec postgres pg_isready; then \
			sleep 1; \
			exit 0; \
		fi; \
		sleep 5; \
	done; \
	echo "Postgres did not become ready in time. Exiting."; \


## Quality

sonar: ## Run the sonar analysis
	@$(COMPOSE_RUNNER) run --rm --entrypoint "/bin/sonar-scanner" app -Dsonar.host.url=https://sonarcloud.io -X

##@ Docs

help: ## Print the makefile help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
