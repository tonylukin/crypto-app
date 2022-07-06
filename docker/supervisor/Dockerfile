FROM docker.onmoon.ru:4567/maykl/containers:php-7.4-fpm
RUN apt-get update && apt-get install -y git supervisor

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

ADD php-ini-overrides.ini /etc/php/7.2/fpm/conf.d/99-overrides.ini
ADD php.ini /etc/php/7.2/cli/php.ini

COPY id_rsa /root/.ssh/id_rsa
RUN chmod 0600 /root/.ssh/id_rsa
