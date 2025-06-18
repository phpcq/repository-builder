FROM composer:latest AS composer

FROM php:8.3-cli-bookworm
LABEL maintainer="Christian Schiffler <christian.schiffler@econsor.de>"

ENV DEBIAN_FRONTEND=noninteractive
RUN apt update \
    && apt upgrade -y \
    && apt install -y --no-install-recommends \
      bash \
      bash-completion \
      gpg \
      libicu-dev \
      locales \
      unzip \
      wget \
      zip \
    && docker-php-ext-install -j$(nproc) intl \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt purge -y libicu-dev --autoremove \
    && rm -rf /var/lib/apt/lists/*

# Uncomment en_US.UTF-8 for inclusion in generation and generate locale
RUN sed -i 's/^# *\(en_US.UTF-8\)/\1/' /etc/locale.gen \
    && locale-gen

COPY <<EOF /usr/local/etc/php/conf.d/99-customization.ini
always_populate_raw_post_data = -1
max_execution_time = 240
max_input_vars = 1500
upload_max_filesize = 8G
post_max_size = 8G

expose_php = Off
memory_limit = 512M

# Configure Xdebug
xdebug.start_with_request=yes
xdebug.mode=debug
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.max_nesting_level=-1
xdebug.log = "/dev/null"
EOF

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN cat >> /etc/bash.bashrc <<EOF
PS1='\$(pwd) > '
source /etc/bash_completion
source <(/project/bin/console completion bash)
EOF

ENV HOME=/project
WORKDIR /project
