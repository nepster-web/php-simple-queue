PROJECT_NAME = php-simple-queue

USER_ID = $(shell id -u)
GROUP_ID=$(shell id -g)

APP_CONTAINER_NAME := app

app_run := $(docker_compose_dev) exec --user="${USER_ID}" -T $(APP_CONTAINER_NAME)

.PHONY : help build start stop restart composer

.DEFAULT_GOAL := help

help: ## show this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## full start application
	$(docker_compose_dev) build --build-arg --build-arg USER_ID=${USER_ID} --build-arg GROUP_ID=${GROUP_ID} --no-cache

start: ## start all containers (in background)
	make composer cmd=install

stop: ## stop all containers
	$(docker_compose_dev) down

restart: ## restart all containers
	make stop || true
	make start

composer: ## run composer
    ifneq ($(cmd),)
		$(app_run) sh -c "composer $(cmd)"
    else
	    $(app_run) sh -c "composer update"
    endif

test: ## run all tests
	$(app_run) sh -c "/app/vendor/bin/phpunit --colors=always"

test-coverage: ## run test coverage
	$(eval APP_ENV=testing /app/vendor/bin/phpunit --colors=always --stderr)