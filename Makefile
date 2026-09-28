.PHONY: up down reset seed test logs build

up:
	docker compose up --build -d

down:
	docker compose down

build:
	docker compose build --pull

reset:
	docker compose down -v
	docker compose up --build -d

seed:
	docker compose exec shop-php-fpm php artisan migrate:fresh --seed --force

test:
	docker compose exec shop-php-fpm php artisan test
	docker compose exec shop-php-fpm ./vendor/bin/phpstan analyse
	docker compose exec shop-php-fpm composer audit

logs:
	docker compose logs -f --tail=200
