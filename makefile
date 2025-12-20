#---Symfony-And-Docker-Makefile---------------#
# Author: https://github.com/yoanbernabeu
# License: MIT
#---------------------------------------------#

#---VARIABLES---------------------------------#
#---DOCKER---#
DOCKER = docker
DOCKER_RUN = $(DOCKER) run
DOCKER_COMPOSE = docker compose
DOCKER_COMPOSE_UP = $(DOCKER_COMPOSE) up -d
DOCKER_COMPOSE_STOP = $(DOCKER_COMPOSE) stop
#------------#
PHP ?= "D:\\SERVER-WEB\\wamp64\\bin\\php\\php8.3.10php.exe"
#---SYMFONY--#
SYMFONY = symfony
SYMFONY_SERVER_START = $(SYMFONY) serve -d
SYMFONY_SERVER_STOP = $(SYMFONY) server:stop
SYMFONY_CONSOLE = $(SYMFONY) console
SYMFONY_LINT = $(SYMFONY_CONSOLE) lint:
#------------#

#---COMPOSER-#
COMPOSER = composer
COMPOSER_INSTALL = $(COMPOSER) install
COMPOSER_UPDATE = $(COMPOSER) update
#------------#

#---MKCERT-#
MKCERT = mkcert
MKCERT_INSTALL = $(MKCERT) -install
#------------#

#---NPM-----#
NPM = npm
NPM_INSTALL = $(NPM) install --force
NPM_UPDATE = $(NPM) update
NPM_BUILD = $(NPM) run build
NPM_DEV = $(NPM) run dev
NPM_WATCH = $(NPM) run watch
#------------#

#---PHPQA---#
PHPQA = jakzal/phpqa
PHPQA_RUN = $(DOCKER_RUN) --init -it --rm -v $(PWD):/project -w /project $(PHPQA)
#------------#

#---PHPUNIT-#
PHPUNIT = APP_ENV=test $(SYMFONY) php bin/phpunit
#------------#
#---------------------------------------------#

## ===  MAKEFILE ================================================
##  make set-ssl domain="paris.wip"
## MKCERT -key-file ./config/ssl/_wildcard.Domaine-key.pem -cert-file ./config/ssl/_wildcard.Domaine.pem *.Domaine.wip
# MKCERT -key-file ./config/ssl-neonatis/neonatis-key.pem -cert-file ./config/ssl-neonatis/neonatis.pem *.neonatis.com neonatis.com

set-ssl:
	IF exist "./config/ssl" ( echo "./config/ssl" exists ) ELSE ( mkdir "./config/ssl" && echo "./config/ssl" created)
	$(MKCERT) -key-file ./config/ssl/$(domain)-key.pem -cert-file ./config/ssl/$(domain).pem *.$(domain)

link-asset:
	cmd /c mklink /d .canvas D:\Developpement\Web\PhpstormProjects\assets\canvas\7.1.1
	cmd /c mklink /d .server-web D:\SERVER-WEB\wamp64\bin

link-asset-canvas:
##  make link-asset-canvas version="7.2.2"
	cmd /c mklink /d .canvas-$(version) D:\Developpement\Web\PhpstormProjects\assets\canvas\$(version)

xdebug:
##  make link-asset-canvas version="7.2.2"
	 XDEBUG_TRIGGER=1 $(SYMFONY_CONSOLE) neox:encryptor:wasaaaa


# SSH command with optional port
SSH_CMD = ssh -p 220 xorg@neonatis.com
# SSH into the server, switch to root, and run the Docker and Symfony commands
ssh:
	@echo "Connecting to xorg@neonatis.com on port 220..."
# 	@$(SSH_CMD)

## === 🆘  HELP =================================================='sudo -i '
help: ## Show this help.
	@echo "Symfony-And-Docker-Makefile"
	@echo "---------------------------"
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
#---------------------------------------------#

## === 🐋  DOCKER ================================================
docker-up: ## Start docker containers.
	$(DOCKER_COMPOSE_UP)
.PHONY: docker-up

docker-stop: ## Stop docker containers.
	$(DOCKER_COMPOSE_STOP)
.PHONY: docker-stop
#---------------------------------------------#

## === 🎛️  SYMFONY ===============================================
sf: ## List and Use All Symfony commands (make sf command="commande-name").
	$(SYMFONY_CONSOLE) $(command)
.PHONY: sf

sf-start: ## Start symfony server.
	$(SYMFONY_SERVER_START)
.PHONY: sf-start

sf-stop: ## Stop symfony server.
	$(SYMFONY_SERVER_STOP)
.PHONY: sf-stop

sf-cc: ## Clear symfony cache.
	$(SYMFONY_CONSOLE) cache:clear
.PHONY: sf-cc

sf-del-cc: ## Clear Symfony cache and delete dev and prod cache directories.
	rm -rf var/cache/dev var/cache/prod
.PHONY: sf-del-cc

sf-log: ## Show symfony logs.
	$(SYMFONY) server:log
.PHONY: sf-log

sf-dc: ## Create symfony database.
	$(SYMFONY_CONSOLE) doctrine:database:create --if-not-exists
.PHONY: sf-dc

sf-dd: ## Drop symfony database.
	$(SYMFONY_CONSOLE) doctrine:database:drop --if-exists --force
.PHONY: sf-dd

sf-su: ## Update symfony schema database.
	$(SYMFONY_CONSOLE) doctrine:schema:update --force
.PHONY: sf-su

sf-mm: ## Make migrations.
	$(SYMFONY_CONSOLE) make:migration
.PHONY: sf-mm

sf-dmm: ## Migrate.
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction
.PHONY: sf-dmm

sf-dsu-cc: ## Migrate.
	$(SYMFONY_CONSOLE) doctrine:schema:update --force && $(SYMFONY_CONSOLE) cache:clear
.PHONY: sf-dsu

sf-ass-compile: ## Show symfony logs.
	$(SYMFONY_CONSOLE) asset-map:compile
.PHONY: sf-ass-compile

sf-fixtures: ## Load fixtures.
	$(SYMFONY_CONSOLE) doctrine:fixtures:load --no-interaction
.PHONY: sf-fixtures

sf-me: ## Make symfony entity
	$(SYMFONY_CONSOLE) make:entity
.PHONY: sf-me

sf-mc: ## Make symfony controller
	$(SYMFONY_CONSOLE) make:controller
.PHONY: sf-mc

sf-mf: ## Make symfony Form
	$(SYMFONY_CONSOLE) make:form
.PHONY: sf-mf

sf-perm: ## Fix permissions.
	chmod -R 777 var
.PHONY: sf-perm

alias-sc: ## Alias symfony & composer.
	@echo "alias s='symfony console'" >> ~/.bashrc
	@echo "alias c='symfony composer'" >> ~/.bashrc
	@echo "Les alias ont été ajoutés à ~/.bashrc. Exécutez 'source ~/.bashrc' pour les appliquer."
.PHONY: alias-sc

sf-sudo-perm: ## Fix permissions with sudo.
	sudo chmod -R 777 var
.PHONY: sf-sudo-perm

sf-dump-env: ## Dump env.
	$(SYMFONY_CONSOLE) debug:dotenv
.PHONY: sf-dump-env

sf-dump-env-container: ## Dump Env container.
	$(SYMFONY_CONSOLE) debug:container --env-vars
.PHONY: sf-dump-env-container

sf-dump-routes: ## Dump routes.
	$(SYMFONY_CONSOLE) debug:router
.PHONY: sf-dump-routes

sf-open: ## Open project in a browser.
	$(SYMFONY) open:local
.PHONY: sf-open

sf-open-email: ## Open Email catcher.
	$(SYMFONY) open:local:webmail
.PHONY: sf-open-email

sf-check-requirements: ## Check requirements.
	$(SYMFONY) check:requirements
.PHONY: sf-check-requirements
#---------------------------------------------#

## === 📦  COMPOSER ==============================================
composer-install: ## Install composer dependencies.
	$(COMPOSER_INSTALL)
.PHONY: composer-install

composer-update: ## Update composer dependencies.
	$(COMPOSER_UPDATE)
.PHONY: composer-update

composer-update-package: ## Mettre à jour des packages Composer spécifiques (exemple : make composer-update-package package="vendor/monpaquet autre/package")
	$(COMPOSER) update $(package)
.PHONY: composer-update-package

composer-validate: ## Validate composer.json file.
	$(COMPOSER) validate
.PHONY: composer-validate

composer-validate-deep: ## Validate composer.json and composer.lock files in strict mode.
	$(COMPOSER) validate --strict --check-lock
.PHONY: composer-validate-deep
#---------------------------------------------#

## === 📦  NPM ===================================================
npm-install: ## Install npm dependencies.
	$(NPM_INSTALL)
.PHONY: npm-install

npm-update: ## Update npm dependencies.
	$(NPM_UPDATE)
.PHONY: npm-update

npm-build: ## Build assets.
	$(NPM_BUILD)
.PHONY: npm-build

npm-dev: ## Build assets in dev mode.
	$(NPM_DEV)
.PHONY: npm-dev

npm-watch: ## Watch assets.
	$(NPM_WATCH)
.PHONY: npm-watch
#---------------------------------------------#

## === 🐛  PHPQA =================================================
qa-cs-fixer-dry-run: ## Run php-cs-fixer in dry-run mode.
	$(PHPQA_RUN) php-cs-fixer fix ./src --rules=@Symfony --verbose --dry-run
.PHONY: qa-cs-fixer-dry-run

qa-cs-fixer: ## Run php-cs-fixer.
	$(PHPQA_RUN) php-cs-fixer fix ./src --rules=@Symfony --verbose
.PHONY: qa-cs-fixer

qa-phpstan: ## Run phpstan.
	$(PHPQA_RUN) vendor/bin/phpstan analyse ./src --level=7
.PHONY: qa-phpstan

qa-security-checker: ## Run security-checker.
	$(SYMFONY) security:check
.PHONY: qa-security-checker

qa-phpcpd: ## Run phpcpd (copy/paste detector).
	$(PHPQA_RUN) phpcpd ./src
.PHONY: qa-phpcpd

qa-php-metrics: ## Run php-metrics.
	$(PHPQA_RUN) phpmetrics --report-html=var/phpmetrics ./src
.PHONY: qa-php-metrics

qa-lint-twigs: ## Lint twig files.
	$(SYMFONY_LINT)twig ./templates
.PHONY: qa-lint-twigs

qa-lint-yaml: ## Lint yaml files.
	$(SYMFONY_LINT)yaml ./config
.PHONY: qa-lint-yaml

qa-lint-container: ## Lint container.
	$(SYMFONY_LINT)container
.PHONY: qa-lint-container

qa-lint-schema: ## Lint Doctrine schema.
	$(SYMFONY_CONSOLE) doctrine:schema:validate --skip-sync -vvv --no-interaction
.PHONY: qa-lint-schema

qa-audit: ## Run composer audit.
	$(COMPOSER) audit
.PHONY: qa-audit
#---------------------------------------------#

## === 🔎  TESTS =================================================
tests: ## Run tests.
	$(PHPUNIT) --testdox
.PHONY: tests

tests-coverage: ## Run tests with coverage.
	$(PHPUNIT) --coverage-html var/coverage
.PHONY: tests-coverage
#---------------------------------------------#

## === ⭐  OTHERS =================================================
before-commit: qa-cs-fixer qa-phpstan qa-security-checker qa-phpcpd qa-lint-twigs qa-lint-yaml qa-lint-container qa-lint-schema tests ## Run before commit.
.PHONY: before-commit

first-install: docker-up composer-install npm-install npm-build sf-perm sf-dc sf-dmm sf-start sf-open ## First install.
.PHONY: first-install

start: docker-up sf-start sf-open ## Start project.
.PHONY: start

stop: docker-stop sf-stop ## Stop project.
.PHONY: stop

reset-db: ## Reset database.
	$(eval CONFIRM := $(shell read -p "Are you sure you want to reset the database? [y/N] " CONFIRM && echo $${CONFIRM:-N}))
	@if [ "$(CONFIRM)" = "y" ]; then \
		$(MAKE) sf-dd; \
		$(MAKE) sf-dc; \
		$(MAKE) sf-dmm; \
	fi
.PHONY: reset-db
#---------------------------------------------#
ifeq ($(OS),Windows_NT)
    # Votre binaire PHP 8.3 (optionnel si PATH déjà OK)
    PHP_PATH := D:\\SERVER-WEB\\wamp64\\bin\\php\\php8.3.10\\php.exe

    PHPSTAN_CMD := vendor\\bin\\phpstan.bat
    CSFIXER_CMD := vendor\\bin\\php-cs-fixer.bat
    PHPUNIT_CMD := vendor\\bin\\phpunit.bat
else
    PHP_PATH := php

    PHPSTAN_CMD := $(PHP_PATH) vendor/bin/phpstan
    CSFIXER_CMD := $(PHP_PATH) vendor/bin/php-cs-fixer
    PHPUNIT_CMD := $(PHP_PATH) vendor/bin/phpunit
endif

.PHONY: analyse cs-fix lint test

analyse:
	$(PHPSTAN_CMD) analyse --configuration=phpstan.neon.dist --level=7

cs-fix:
	$(CSFIXER_CMD) fix --config=.php-cs-fixer.dist.php

lint:
	$(CSFIXER_CMD) fix --config=.php-cs-fixer.dist.php --dry-run --diff

test:
	$(PHPUNIT_CMD) -c phpunit.xml.dist