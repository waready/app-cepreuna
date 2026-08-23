# Despliegue de app-cepreuna

El despliegue usa releases atomicos y nunca ejecuta migraciones. Un fallo de Composer, PHP o del health check mantiene o restaura el release anterior.

## Preparacion unica del servidor

Ejecutar desde una copia del proyecto en `10.1.20.59`. Si las rutas fueron creadas por `root`, usar:

```bash
sudo env DEPLOY_USER=developer WEB_GROUP=www-data \
  EXISTING_RELEASE=/var/www/app-cepreuna/releases/release-actual \
  bash deploy/install-server.sh
```

Confirmar que Nginx use:

```nginx
root /var/www/app-cepreuna/current/public;
```

Opcionalmente crear `/var/www/app-cepreuna/shared/deploy.env`:

```bash
PHP_BIN=/usr/bin/php8.3
COMPOSER_BIN=/var/www/app-cepreuna/shared/bin/composer.phar
BUILD_FRONTEND=0
HEALTH_URL=https://app.cepreuna.edu.pe/
RELOAD_CMD="sudo -n systemctl reload php8.3-fpm"
```

`BUILD_FRONTEND=0` usa los assets compilados incluidos en Git. No agregar credenciales a `deploy.env`; Laravel las toma del `.env` compartido.

## Uso desde desarrollo

El remoto se configura una sola vez:

```bash
git remote add deploy ssh://developer@10.1.20.59/var/repositories/app-cepreuna.git
```

Cada despliegue se inicia con:

```bash
git push deploy main
```

El hook instala dependencias PHP, limpia y genera caches, cambia el symlink, prueba la URL publica y revierte si la prueba falla. No ejecuta `php artisan migrate`.
