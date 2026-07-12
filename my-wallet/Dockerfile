FROM richarvey/nginx-php-fpm:3.1.6

# Copy application files to /var/www/html
COPY . .

# Set up required variables for richarvey/nginx-php-fpm image
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel environment variables defaults (overridden by Render env vars)
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install composer dependencies during build
RUN composer install --no-dev --optimize-autoloader

# Install nodejs, npm, and build Vite assets
RUN apk add --no-cache npm && \
    npm install && \
    npm run build

CMD ["/start.sh"]
