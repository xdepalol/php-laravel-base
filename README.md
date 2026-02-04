# Laravel 10 + Vue 3 API Base Project

Este proyecto es una base sólida diseñada para estudiantes y desarrolladores que deseen aprender a construir aplicaciones SPA (Single Page Application) modernas utilizando Laravel como API backend y Vue 3 como frontend.

## 🚀 Características Principales

### Backend (Laravel 10)
- **API RESTful**: Estructura robusta para servir datos al frontend.
- **Autenticación Sanctum**: Sistema seguro de autenticación basado en cookies/tokens.
- **Roles y Permisos**: Implementación de `spatie/laravel-permission` para gestión granular de accesos.
- **Recursos API**: Uso de API Resources para transformar datos de manera consistente.

### Frontend (Vue 3)
- **Composition API**: Uso moderno de Vue 3 con `<script setup>`.
- **Pinia**: Gestión de estado modular y persistente.
- **Vue Router**: Enrutamiento dinámico con protecciones de navegación (Guards).
- **PrimeVue**: Suite de componentes UI profesional y personalizable.
- **Tailwind CSS**: Estilizado utilitario para un diseño rápido y responsivo.
- **i18n**: Soporte multi-idioma (Español, Inglés, Francés, etc.).
- **Validación**: Formularios robustos con `yup`

## 🛠️ Requisitos Previos

- PHP >= 8.1
- Composer
- Node.js >= 16
- MySQL / MariaDB

## ⚙️ Instalación y Configuración

Sigue estos pasos para levantar el proyecto en tu entorno local:

### 1. Clonar el Repositorio
```bash
git clone <url-del-repositorio>
cd Laravel-VUE-API-Base-Clase
```

### 2. Configurar Backend (Laravel)

Instalar dependencias de PHP:
```bash
composer install
```

Configurar variables de entorno:
```bash
cp .env.example .env
```

Generar clave de aplicación:
```bash
php artisan key:generate
```

Configurar base de datos en `.env`:
Abre el archivo `.env` y ajusta las credenciales de tu base de datos:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_bd
DB_USERNAME=root
DB_PASSWORD=
```

Configurar dominio para Sanctum (Importante para autenticación):
```dotenv
SANCTUM_STATEFUL_DOMAINS=localhost:8000
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:8000
```

Ejecutar migraciones y seeders:
```bash
php artisan migrate --seed
```
*Esto creará, categorías para un blog, usuarios, roles y permisos iniciales.*

### Credenciales de Acceso (Seeders)
Los siguientes usuarios son creaados por defecto:
- **Admin**: `admin@demo.com` / `12345678`
- **Usuario**: `user@demo.com` / `12345678`

### 3. Configurar Frontend (Vue)

Instalar dependencias de Node:
```bash
npm install
```

### 4. Ejecutar la Aplicación

Necesitarás dos terminales:

Terminal 1 (Backend):
```bash
php artisan serve
```

Terminal 2 (Frontend):
```bash
npm run dev
```

Accede a la aplicación en: `http://localhost:8000`

## 📂 Estructura del Proyecto

### Backend (`app/`)
- `Http/Controllers/Api`: Controladores que manejan las peticiones API.
- `Http/Resources`: Transformadores de datos JSON.
- `Models`: Modelos Eloquent.

### Frontend (`resources/js/`)
- `components`: Componentes Vue reutilizables (Botones, Inputs, etc.).
- `composables`: Lógica reutilizable (Hooks) para API, validación, etc.
- `layouts`: Plantillas principales (Admin, User, Guest).
- `pages` / `views`: Vistas de la aplicación organizadas por módulos.
- `store`: Estados globales con Pinia (Auth, Lang, etc.).
- `routes`: Definición de rutas y guards.

# Notes de aprendizaje

## Comandos scaffolding

**Crea model** i fitxer de **migració**
```
php artisan make:model Student --migration
```

Després de definir la taula al fitxer de migració, creem la resta de **components API**:
```
php artisan make:controller StudentController --api
```

Crear un fitxer de **seeder**
```
php artisan make:seeder StudentSeeder
```

**Executar un seeder** específic
```
php artisan db:seed --class=StudentSeeder
```

Desfer el darrer fitxer de migració (si ens hem equivocat i volem repetir-lo però bé)
```
php artisan migrate:rollback
```

Crea model amb el control·lador (-c) i model (-m)
```
php artisan make:model Post -c -m
```

## Base de dades

Actualitzar el model

```
php artisan migrate
```

## Documentació

### Paquets

```
composer require symfony/html-sanitizer
```

### Laravel

#### Controllers

https://laravel.com/docs/12.x/controllers

## Errors

Aquesta API utilitza content negotiation segons l’estàndard HTTP.

Per defecte, si no s’especifica la capçalera Accept, Laravel pot retornar una resposta HTML (comportament web). Per obtenir **respostes JSON** pròpies d’una API, és obligatori enviar la capçalera:

```
Accept: application/json
```

**Petició no conforme a model (sense `Accept: application/json`)**

```yaml
POST /api/posts
{
    "title": "SaL",
    "content": "Les migracions permeten versionar l'estructura de la base de dades.",
    "user_id": 2
}
```
Resposta
```yaml
HTTP/1.1 200 OK
Content-Type: text/html
<html>
...
</html>
```

Aquest comportament es correspon a una petició tractada com a **web**, no com a API.

**Petició correcte API (amb `Accept: application/json`)**

Per obtenir la resposta en HTML amb l'error s'ha d'enviar la capçalera `Header: application/json`.

```yaml
POST /api/posts
Accept: application/json
Content-Type: application/json
{
    "title": "SaL",
    "content": "Les migracions permeten versionar l'estructura de la base de dades.",
    "user_id": 2
}
```
Resposta
```yaml
HTTP/1.1 422 Unprocessable content
{
    "message": "The title must be at least 5",
    "errors": {
        "title": [
            "The title must be at least 5 characters."
        ]
    }
}
```

Aquesta és la resposta esperada i correcta d'una API REST.