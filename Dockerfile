FROM php:8.2-apache

# Install system dependencies & libraries required for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    ca-certificates \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules: rewrite, headers
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy all project files into Apache document root
COPY . /var/www/html/

# Fix permissions and line endings for start script
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && sed -i 's/\r$//' /var/www/html/start.sh \
    && chmod +x /var/www/html/start.sh

# Expose default port (Render will inject $PORT)
EXPOSE 80

# Run Apache via startup script
CMD ["/var/www/html/start.sh"]

