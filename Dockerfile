# Usamos una imagen oficial de Render con PHP 8.2 y Node
FROM render/php:8.2-fpm-node

# Copiamos todo el código de la app
COPY . .

# Ejecutamos todos los comandos de construcción en orden
RUN composer install --no-dev --optimize-autoloader && \
    npm install && \
    npm run build && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan storage:link

# El comando para iniciar el servidor
CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "80"]