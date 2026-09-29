.PHONY: up start down reset seed test audit logs build

up:
	docker compose up --build

start:
	docker compose up --build -d --wait

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
	docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_STORE=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array shop-php-fpm php artisan test
	docker compose exec shop-php-fpm ./vendor/bin/phpstan analyse

audit:
	docker run --rm --volume "$(CURDIR)/services/shop:/app:ro" --workdir /app composer:2.8 audit --locked --no-interaction

logs:
	docker compose logs -f --tail=200
