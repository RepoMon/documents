FROM php:latest

MAINTAINER Tim Rodger <tim.rodger@gmail.com>

RUN apt-get update -qq && \
    apt-get install -y \
    curl \
    libicu-dev \
    zip \
    unzip \
    git

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/bin/composer

CMD ["/home/app/run.sh"]

# Move application files into place
COPY src/ /home/app/

WORKDIR /home/app

# Install dependencies
RUN composer install --prefer-dist && \
    apt-get clean

RUN chmod +x run.sh

USER root

