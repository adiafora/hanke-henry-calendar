build:
	docker-compose up -d --build

composer:
	docker-compose exec php composer install

start:
	docker-compose up -d

stop:
	docker-compose down

sh:
	docker-compose exec php sh

test:
	docker-compose exec php vendor/bin/phpunit

cs:
	docker-compose exec php vendor/bin/php-cs-fixer fix

phpstan:
	docker-compose exec php vendor/bin/phpstan analyse

ci: cs phpstan test
