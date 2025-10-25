FROM php:8.2-fpm

# Cài đặt các gói cần thiết cho Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_mysql mysqli gd

# Cài đặt Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Thêm file cấu hình Xdebug
COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Cài Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy toàn bộ project vào container
COPY . /var/www/html

# Đặt thư mục làm việc mặc định
WORKDIR /var/www/html

# Cấp quyền cho thư mục storage & bootstrap/cache (Laravel cần)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose cổng PHP-FPM
EXPOSE 9000

# Lệnh mặc định để chạy PHP-FPM
CMD ["php-fpm"]
