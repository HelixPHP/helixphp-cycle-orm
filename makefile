
.PHONY: help install test lint fix analyse coverage clean

# Cores para output
YELLOW=\033[33m
GREEN=\033[32m
RED=\033[31m
BLUE=\033[34m
NC=\033[0m # No Color

help: ## Mostrar este help
	@echo "${BLUE}Express-PHP Cycle ORM Extension${NC}"
	@echo "${BLUE}================================${NC}"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "${YELLOW}%-20s${NC} %s\n", $$1, $$2}'

install: ## Instalar dependências
	@echo "${GREEN}Installing dependencies...${NC}"
	composer install

install-dev: ## Instalar dependências de desenvolvimento
	@echo "${GREEN}Installing dev dependencies...${NC}"
	composer install --dev

test: ## Executar testes
	@echo "${GREEN}Running tests...${NC}"
	vendor/bin/phpunit

test-coverage: ## Executar testes com coverage
	@echo "${GREEN}Running tests with coverage...${NC}"
	vendor/bin/phpunit --coverage-html=coverage-html --coverage-clover=coverage.xml

test-integration: ## Executar apenas testes de integração
	@echo "${GREEN}Running integration tests...${NC}"
	vendor/bin/phpunit tests/Integration/

lint: ## Verificar code style
	@echo "${GREEN}Checking code style...${NC}"
	vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Corrigir code style
	@echo "${GREEN}Fixing code style...${NC}"
	vendor/bin/php-cs-fixer fix

analyse: ## Executar análise estática
	@echo "${GREEN}Running static analysis...${NC}"
	vendor/bin/phpstan analyse

ci: ## Executar pipeline completo de CI
	@echo "${GREEN}Running full CI pipeline...${NC}"
	make lint
	make analyse
	make test

clean: ## Limpar cache e artifacts
	@echo "${GREEN}Cleaning up...${NC}"
	rm -rf coverage-html/
	rm -f coverage.xml
	rm -f junit.xml
	rm -rf .phpunit.cache/
	rm -rf vendor/
	composer clear-cache

docs: ## Gerar documentação
	@echo "${GREEN}Generating documentation...${NC}"
	@echo "Documentation available in docs/ directory"

demo: ## Executar demo básico
	@echo "${GREEN}Running basic demo...${NC}"
	php examples/basic-usage.php

benchmark: ## Executar benchmarks
	@echo "${GREEN}Running benchmarks...${NC}"
	@echo "Benchmark scripts would go here..."

docker-test: ## Executar testes em Docker
	@echo "${GREEN}Running tests in Docker...${NC}"
	docker run --rm -v $(PWD):/app -w /app php:8.1-cli composer install && vendor/bin/phpunit

security: ## Verificar vulnerabilidades de segurança
	@echo "${GREEN}Checking for security vulnerabilities...${NC}"
	composer audit

update: ## Atualizar dependências
	@echo "${GREEN}Updating dependencies...${NC}"
	composer update

psalm: ## Executar Psalm (se disponível)
	@echo "${GREEN}Running Psalm...${NC}"
	@if [ -f vendor/bin/psalm ]; then vendor/bin/psalm; else echo "Psalm not installed"; fi