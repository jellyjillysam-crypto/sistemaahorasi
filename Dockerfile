# 1. Imagen con PHP 8.3 y Apache
FROM php:8.3-apache

# 2. Extensiones para conectarse a MySQL (mysqli y PDO)
RUN docker-php-ext-install mysqli pdo_mysql

# 3. Apache: leer .htaccess y abrir inicio.html como pagina principal
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && echo "DirectoryIndex inicio.html index.php" > /etc/apache2/conf-available/portada.conf \
 && a2enconf portada

# 4. Copiamos el proyecto a la carpeta publica de Apache
COPY . /var/www/html/

# 5. Apache escucha en el puerto que Render indique
ENV PORT=10000
RUN sed -i 's/80/${PORT}/g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/ports.conf
