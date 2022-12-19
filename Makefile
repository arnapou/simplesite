.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

COMPOSER_OPTS=--no-interaction --no-progress --optimize-autoloader --classmap-authoritative
PHAR_FILENAME=site/simplesite.phar

build: all prebuild ## build phar
	php -d "phar.readonly=Off" ./build/build.php ${PHAR_FILENAME}
	@ls -lah --color ${PHAR_FILENAME}
	@make -s install

build-list: all prebuild ## list phar files
	php -d "phar.readonly=Off" ./build/printfiles.php
	@make -s install

all: install cs analysis ## code style + analysis

analysis: ## static analysis
	vendor/bin/psalm --no-cache
	vendor/bin/phpstan

cs: ## code style
	vendor/bin/php-cs-fixer fix --using-cache=no

install: ## composer install
	composer install $(COMPOSER_OPTS) --quiet
	@make -s hack-php-cs-fixer

update: ## composer update
	composer update $(COMPOSER_OPTS)
	@make -s hack-php-cs-fixer

prebuild:
	composer install $(COMPOSER_OPTS) --quiet --no-dev

hack-php-cs-fixer:
# hack PHP_CS_FIXER_IGNORE_ENV=1 into the script (php 8.2 ot supported yet)
	@sed -i -E 's/getenv\(.PHP_CS_FIXER_IGNORE_ENV.\)/1/' vendor/friendsofphp/php-cs-fixer/php-cs-fixer
