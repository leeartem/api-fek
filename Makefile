up:
	docker-compose up -d --build
run:
	docker-compose up --build
stop:
	docker-compose stop 
o:
	docker-compose run --rm artisan optimize