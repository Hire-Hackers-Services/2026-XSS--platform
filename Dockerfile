FROM php:8.1-fpm-alpine

# 安装系统依赖和PHP扩展
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        gd \
        opcache

# 安装Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 创建工作目录
WORKDIR /var/www/html

# 复制应用文件
COPY . /var/www/html/

# 创建必要的目录
RUN mkdir -p \
    /var/www/html/data \
    /var/www/html/data/backups \
    /var/www/html/myjs \
    /var/www/html/jstemplates \
    /var/log/nginx \
    /var/log/php \
    /run/nginx \
    && chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/html

# 复制Nginx配置
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# 复制Supervisor配置
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 复制PHP配置
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# 复制环境变量文件（如果不存在则从example复制）
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# 暴露端口
EXPOSE 80

# 启动Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
