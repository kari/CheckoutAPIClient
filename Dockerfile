FROM php:5.6

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip && \
    curl --silent --show-error https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

RUN groupadd -r app &&\
    useradd -r -g app -d /home/app -s /sbin/nologin -c "Docker image user" app

ENV HOME=/home/app

COPY . $HOME

RUN chown -R app:app $HOME
WORKDIR $HOME

USER app

RUN curl --silent --show-error https://getcomposer.org/installer | php && \
    composer install --prefer-source --no-interaction

CMD [ "./vendor/bin/phpspec", "run" ]
