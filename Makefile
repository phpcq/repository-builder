
MKFILE_PATH := $(abspath $(lastword $(MAKEFILE_LIST)))
MKFILE_DIR := $(dir $(MKFILE_PATH))

PROJECT_ROOT=$(MKFILE_DIR)
UID="$(shell id -u)"
GID="$(shell id -g)"

COMPOSE_CMD= \
	PROJECT_ROOT=$(PROJECT_ROOT) \
	UID=${UID} \
	GID=${GID} \
	docker compose

.PHONY: all
all: help

.PHONY: help
help:
	# To ease development, we are offering the following make commands:
	#     make composer-install        Install composer dependencies.
	#     make composer-update         Update composer dependencies.
	#
	#     make php-shell               Run a shell in the php container (You need this for PHP CLI!).
	#     make php-shell-debug         Run a shell in the php container in debug mode (You need this for PHP CLI!).

.PHONY: build
build:
	COMPOSE_BAKE=true $(COMPOSE_CMD) build

.PHONY: down
down:
	$(COMPOSE_CMD) down

.PHONY: composer-install
composer-install: build
	$(COMPOSE_CMD) run --rm -e XDEBUG_MODE=off php composer install --prefer-dist --no-interaction --no-progress -v

.PHONY: composer-update
composer-update: build
	$(COMPOSE_CMD) run --rm -e XDEBUG_MODE=off php composer update --prefer-dist --no-interaction --no-progress -v

.PHONY: php-shell
php-shell: build
	$(COMPOSE_CMD) run --rm -e XDEBUG_MODE=off php /bin/bash

.PHONY: php-shell-debug
php-shell-debug: build
	$(COMPOSE_CMD) run --rm php /bin/bash

.PHONY: tests
tests: build
	$(COMPOSE_CMD) run --rm php vendor/bin/phpcq install -vvv
	$(COMPOSE_CMD) run --rm php vendor/bin/phpcq run -o default -r code-climate -vvv
