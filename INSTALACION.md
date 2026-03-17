# MP Admin VPS – Instalación

## Requisitos

- PHP 8.1+ (recomendado 8.2+)
- Composer
- MySQL
- Extensiones PHP: xml, curl, pdo_mysql, mbstring, tokenizer

## Pasos

### 1. Instalar dependencias PHP (si falta algo)

```bash
sudo apt install php8.1-xml php8.1-curl php8.1-mysql php8.1-mbstring
```

### 2. Instalar dependencias del proyecto

En la raíz del proyecto (`/var/www/html/mp-admin-vps`):

```bash
composer update --no-interaction
# Si tu PHP es 8.1 y composer se queja de plataforma:
composer update --no-interaction --ignore-platform-reqs
```

Si aún no tienes `spatie/laravel-permission`:

```bash
composer require spatie/laravel-permission
```

### 3. Configuración

- Copia `.env.example` a `.env` si no existe.
- Genera la clave: `php artisan key:generate`
- Configura base de datos en `.env`:

  ```
  APP_NAME="MP Admin VPS"
  APP_URL=http://localhost
  # o APP_URL=https://www.servidormp.com en producción

  DB_DATABASE=mp_admin_vps
  DB_USERNAME=tu_usuario
  DB_PASSWORD=tu_password
  ```

### 4. Base de datos

```bash
php artisan migrate
php artisan db:seed
```

### 5. Acceso inicial

- **URL:** la que tengas en `APP_URL` (ej. `http://localhost/mp-admin-vps/public` o el dominio del servidor).
- **Login:** `admin@mpadmin.local`
- **Contraseña:** `password`

Cambia la contraseña y el email en producción.

### 6. Permisos de almacenamiento (Linux)

```bash
chmod -R 775 storage bootstrap/cache
```

## Estructura de permisos

- **superadmin:** todos los permisos.
- **admin:** usuarios (ver/crear/editar), proyectos, integraciones móviles, logs, settings (ver).
- **editor:** proyectos (ver/editar/acceso), integraciones móviles (ver/editar).
- **viewer:** proyectos (ver/acceso), integraciones móviles (ver).

Los usuarios solo ven proyectos en los que están asignados (tabla `project_user`), salvo que tengan permiso global `projects.view`.
