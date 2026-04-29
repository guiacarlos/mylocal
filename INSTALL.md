# Instalacion de MyLocal

## Requisitos del servidor

- Apache 2.4+ o LiteSpeed con mod_rewrite activado
- PHP >= 7.4 (recomendado 8.1+)
- Extensiones PHP: json, mbstring, curl, openssl
- HTTPS configurado (certificado SSL)

## Pasos de despliegue

### 1. Construir la release

En el equipo de desarrollo ejecutar:

    .\build.ps1

Esto genera la carpeta `release/` con todo lo necesario.

### 2. Subir al servidor

Subir el contenido de `release/` a la raiz del dominio o subdominio.
Se puede usar FTP, rsync, panel de hosting o cualquier metodo disponible.

### 3. Configurar permisos

    chmod -R 755 .
    chmod -R 777 STORAGE/
    chmod -R 777 MEDIA/

### 4. Configurar CORE/config.json

Copiar `config.example.json` como `CORE/config.json` y rellenar:

    cp config.example.json CORE/config.json

Campos obligatorios:
- `storage_root`: ruta absoluta a STORAGE/ (o dejar vacia para autodeteccion)
- `media_root`: ruta absoluta a MEDIA/ (o dejar vacia para autodeteccion)

### 5. Crear usuario administrador

Acceder por SSH o terminal del hosting y ejecutar:

    php CORE/auth/bootstrap_users.php

Seguir las instrucciones en pantalla para crear el primer superadmin.

### 6. Verificar .htaccess

Comprobar que el archivo .htaccess de la raiz esta presente y que
mod_rewrite esta activo en Apache.

Para LiteSpeed no se necesita configuracion adicional, ya que lee
.htaccess de forma nativa.

### 7. Comprobar el acceso

- Pagina publica: https://tudominio.com/
- Panel de gestion: https://tudominio.com/dashboard
- Carta publica: https://tudominio.com/carta/{slug-del-local}

## Estructura de archivos en el servidor

    /                   raiz del dominio
    index.html          SPA compilada
    assets/             JS y CSS minificados
    CORE/               backend PHP
    CAPABILITIES/       modulos de negocio
    axidb/              motor de datos
    STORAGE/            datos del restaurante (escritura)
    MEDIA/              imagenes de productos (escritura)
    .htaccess           enrutamiento Apache
    gateway.php         gateway de autenticacion
    router.php          router PHP

## Migracion de datos

Si es una migracion desde otra instalacion:
1. Copiar la carpeta STORAGE/ del servidor anterior
2. Copiar la carpeta MEDIA/ del servidor anterior
3. Verificar permisos de escritura en ambas carpetas

## Docker (opcional)

    FROM php:8.1-apache
    RUN a2enmod rewrite
    COPY . /var/www/html/
    RUN mkdir -p /var/www/html/STORAGE /var/www/html/MEDIA
    RUN chown -R www-data:www-data /var/www/html/STORAGE /var/www/html/MEDIA
    VOLUME /var/www/html/STORAGE
    VOLUME /var/www/html/MEDIA
    EXPOSE 80

## Soporte

WhatsApp: +34 611 677 577
Email: soporte@mylocal.es
