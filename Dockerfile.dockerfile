FROM php:8.2-apache

# کپی فایل‌ها به مسیر پیش‌فرض Apache
COPY . /var/www/html/

# فعال‌سازی mod_rewrite در صورت نیاز
RUN a2enmod rewrite
