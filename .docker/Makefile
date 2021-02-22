PROJECT_NAME = php-simple-queue

USER_ID = $(shell id -u)
GROUP_ID=$(shell id -g)
APP_DIR="${PWD}/.."

app_run := docker exec -t --user="${USER_ID}" $(PROJECT_NAME)

.PHONY : help build start stop restart composer php test test-coverage

.DEFAULT_GOAL := help

help: ## show this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## build application
	docker build --build-arg USER_ID=${USER_ID} --build-arg GROUP_ID=${GROUP_ID} --no-cache --tag ${PROJECT_NAME} .

start: ## start application (in background)
	docker run -d --name=${PROJECT_NAME} -v ${APP_DIR}:/app ${PROJECT_NAME}
	make composer cmd=install

stop: ## stop all containers
	docker stop ${PROJECT_NAME} && docker rm ${PROJECT_NAME}

restart: ## restart all containers
	make stop || true
	make start

composer: ## run composer
    ifneq ($(cmd),)
		$(app_run) sh -c "composer $(cmd)"
    else
	    $(app_run) sh -c "composer update"
    endif

php: ## run php
    ifneq ($(cmd),)
		$(app_run) sh -c "php $(cmd)"
    else
	    $(app_run) sh -c "php"
    endif

test: ## run all tests
	$(app_run) sh -c "/app/vendor/bin/phpunit --colors=always"

test-coverage: ## run test coverage
	$(eval APP_ENV=testing /app/vendor/bin/phpunit --colors=always --stderr)