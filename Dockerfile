FROM php:8.2-apache

# Instala dependências do sistema e extensões necessárias
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Configurações do PHP para Upload de Vídeos e Performance
RUN echo "upload_max_filesize = 256M\n\
post_max_size = 256M\n\
memory_limit = 256M\n\
max_execution_time = 300\n\
max_input_time = 300\n\
date.timezone = America/Sao_Paulo\n\
display_errors = Off" > /usr/local/etc/php/conf.d/player99.ini

WORKDIR /var/www/html

# Ajuste de permissões padrão
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
