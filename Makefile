all: test

build-test:
	docker build -t cof-php-api-test .

run-test:
	docker run -t cof-php-api-test

.PHONY: test
test: build-test run-test

.PHONY: example
example:
	docker build -f Dockerfile-example -t cof-php-api-examples .
	docker run -it -p 3003:80 -t cof-php-api-examples