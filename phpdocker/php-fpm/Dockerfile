FROM php:8.2-fpm
WORKDIR "/application"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive
ARG PHP_INI_DIR=/usr/local/etc/php
ARG FPM_CONF_DIR=/usr/local/etc/php-fpm.d
ARG OPCACHE_VALIDATE_TIMESTAMPS=0

ARG OPCACHE_INI=${PHP_INI_DIR}/conf.d/opcache.ini

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN echo "[opcache]" >> ${OPCACHE_INI}
RUN echo "opcache.enable=1" >> ${OPCACHE_INI}
RUN echo "opcache.revalidate_freq=0" >> ${OPCACHE_INI}
RUN echo "opcache.validate_timestamps=${OPCACHE_VALIDATE_TIMESTAMPS}" >> ${OPCACHE_INI}
RUN echo "opcache.max_accelerated_files=20000" >> ${OPCACHE_INI}
RUN echo "opcache.memory_consumption=128" >> ${OPCACHE_INI}
RUN echo "opcache.interned_strings_buffer=16" >> ${OPCACHE_INI}
