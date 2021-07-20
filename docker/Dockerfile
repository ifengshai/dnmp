FROM php:7.3-fpm-alpine
MAINTAINER fangke <fangke@nextmar.com>

#bz2 bzip2 bzip2-dev
#zip libzip libzip-dev
#gd freetype freetype-dev libpng libpng-dev libwebp libwebp-dev libjpeg-turbo libjpeg-turbo-dev
#intl icu icu-dev
#gettext gettext gettext-dev
#xsl libxslt libxslt-dev
#soap libxml2 libxml2-dev
#rdkafka librdkafka librdkafka-dev

#安装系统类库
RUN apk add --update --no-cache bzip2-dev freetype-dev libpng-dev libwebp-dev libjpeg-turbo-dev icu-dev gettext-dev libxslt-dev libxml2-dev librdkafka-dev libzip-dev nodejs npm

#创建php扩展安装目录
RUN mkdir -p /usr/src/php/ext/

#安装Redis扩展
ENV PHPREDIS_VERSION 5.3.4
RUN curl -L -o /tmp/redis.tar.gz https://github.com/phpredis/phpredis/archive/$PHPREDIS_VERSION.tar.gz \
    && tar xfz /tmp/redis.tar.gz \
    && rm -r /tmp/redis.tar.gz \
    && mv phpredis-$PHPREDIS_VERSION /usr/src/php/ext/redis

#安装kafka扩展
ENV RDKAFKA_VERSION 5.0.0
RUN curl -L -o /tmp/rdkafka.tar.gz https://github.com/arnaud-lb/php-rdkafka/archive/$RDKAFKA_VERSION.tar.gz \
    && tar xfz /tmp/rdkafka.tar.gz \
    && rm -r /tmp/rdkafka.tar.gz \
    && mv php-rdkafka-$RDKAFKA_VERSION /usr/src/php/ext/rdkafka

#安装PHP扩展
RUN docker-php-ext-configure gd \
    --with-gd \
    --with-freetype-dir=/usr/include/ \
    --with-jpeg-dir=/usr/include/ \
    --with-png-dir=/usr/include/ \
    --with-webp-dir=/usr/include/  && \
  docker-php-ext-install -j$(nproc) bcmath bz2 gd calendar dba exif gettext intl mysqli pcntl pdo_mysql shmop sockets sysvmsg sysvsem sysvshm xsl redis soap rdkafka zip xmlrpc xmlwriter opcache

#全局安装PM2
RUN npm i -g pm2

WORKDIR /var/www/

EXPOSE 9000

CMD ["php-fpm"]