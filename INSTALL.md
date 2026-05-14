# AGRITECH — Guía de Instalación

## Requisitos
- PHP >= 8.2
- Composer
- MySQL 8+
- Node.js (opcional, solo si modificas assets)

## Pasos

```bash
# 1. Clonar/descomprimir el proyecto
cd agritech

# 2. Instalar dependencias PHP
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Editar .env con tus credenciales de base de datos
#    DB_DATABASE=agritech_db
#    DB_USERNAME=tu_usuario
#    DB_PASSWORD=tu_clave

# 5. Crear base de datos MySQL
mysql -u root -p -e "CREATE DATABASE agritech_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Ejecutar migraciones y seeders
php artisan migrate --seed

# 7. Crear enlace de almacenamiento
php artisan storage:link

# 8. Iniciar servidor de desarrollo
php artisan serve
```

## Acceso
- URL: http://localhost:8000
- **Usuario demo:** admin@agritech.co
- **Contraseña demo:** Agritech2024!

## Estructura del Proyecto

```
agritech/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php       ← Registro, login, logout
│   │   │   ├── DashboardController.php  ← Summary + realtime polling
│   │   │   ├── SensorController.php     ← Historial + ingesta datos
│   │   │   └── ReportController.php     ← Generación PDF DomPDF
│   │   └── Middleware/
│   │       └── SecureHeaders.php        ← CSP, X-Frame, XSS headers
│   └── Models/
│       ├── User.php / Plot.php / Sensor.php / Reading.php / Report.php
├── database/
│   ├── migrations/                      ← Esquema completo
│   └── seeders/DatabaseSeeder.php       ← Datos demo (288 lecturas/sensor)
├── resources/views/
│   ├── app.blade.php                    ← SPA shell
│   └── reports/pdf.blade.php            ← Template PDF DomPDF
├── public/
│   ├── css/app.css                      ← Diseño dark IoT dashboard
│   └── js/app.js                        ← SPA completa (sin framework)
├── routes/
│   ├── api.php                          ← Rutas API protegidas Sanctum
│   └── web.php                          ← SPA catch-all
└── bootstrap/app.php                    ← Config Laravel 12
```

## Endpoints API

| Método | Ruta                          | Auth | Descripción               |
|--------|-------------------------------|------|---------------------------|
| POST   | /api/register                 | ❌   | Registro de usuario       |
| POST   | /api/login                    | ❌   | Login → token Sanctum     |
| POST   | /api/logout                   | ✅   | Revocar token             |
| GET    | /api/me                       | ✅   | Usuario autenticado       |
| GET    | /api/dashboard/summary        | ✅   | KPIs + estado sensores    |
| GET    | /api/sensors/realtime         | ✅   | Polling tiempo real       |
| GET    | /api/sensors                  | ✅   | Lista sensores            |
| GET    | /api/sensors/history          | ✅   | Historial ?range=day/week |
| POST   | /api/sensors/reading          | ✅   | Ingesta desde IoT         |
| POST   | /api/reports/generate         | ✅   | Genera PDF                |
| GET    | /api/reports                  | ✅   | Lista reportes            |
| GET    | /api/reports/{id}/download    | ✅   | Descarga PDF              |

## Seguridad OWASP Implementada
- ✅ XSS: Blade `{{ }}` auto-escape + JS `escapeHtml()`
- ✅ CSRF: Laravel CSRF token en meta tag
- ✅ SQL Injection: Eloquent ORM exclusivamente
- ✅ Auth: Sanctum Bearer Token + bcrypt hash
- ✅ Headers: CSP, X-Frame-Options, X-Content-Type-Options, etc.
- ✅ Validación: Form Request en todos los endpoints
- ✅ Tokens: Rotación en cada login, revocación en logout

## Flujo de Datos
```
Sensor IoT
  └─► POST /api/sensors/reading (Token)
       └─► Laravel valida + Eloquent guarda en DB
            └─► Frontend polling GET /api/sensors/realtime cada 5s
                 └─► Chart.js actualiza gráficas dinámicamente
                      └─► DomPDF genera reporte PDF on-demand
```
