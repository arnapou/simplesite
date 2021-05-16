
COMPOSER_OPTIONS=--optimize-autoloader --no-interaction --classmap-authoritative

PHAR_FILENAME=site/simplesite.phar

default: composer
	$(shell echo "<?php require __DIR__.'/../src/main.php';" > site/simplesite.phar)
	vendor/bin/php-cs-fixer fix
	vendor/bin/psalm --no-cache
#	vendor/bin/phpunit

build: default prebuild
	php -d "phar.readonly=Off" ./build/build.php ${PHAR_FILENAME}
	@ls -lah --color ${PHAR_FILENAME}
	@make -s composer

build-printfiles: default prebuild
	php -d "phar.readonly=Off" ./build/printfiles.php
	@make -s composer

composer:
	composer install ${COMPOSER_OPTIONS} --quiet

update:
	composer update ${COMPOSER_OPTIONS}

prebuild:
	rm -Rf vendor
	composer install ${COMPOSER_OPTIONS} --quiet --no-dev
