FROM php:7.2-cli

RUN apt-get update \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

EXPOSE 80/tcp

CMD [ "php", "-S", "0.0.0.0:80", "-t", "/usr/src/myapp/public", "/usr/src/myapp/public/index.php" ]