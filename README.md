# Numerar Contable

Motor contable de **partida doble** para Laravel, diseñado sobre el **Plan Único de Cuentas (PUC) colombiano**. Se instala como paquete Composer en cualquier proyecto Laravel 11 o 12 y expone tanto una interfaz web Blade lista para usar como una API REST JSON completa.

## Características principales

- **Partida doble estricta** — los comprobantes solo se pueden contabilizar si débitos = créditos.
- **PUC colombiano precargado** — 9 clases de cuentas (Activo, Pasivo, Patrimonio, Ingresos, Gastos, Costos de Ventas, Costos de Producción, Cuentas de Orden Deudoras y Acreedoras) listas desde el primer `contable:install`. Opcionalmente carga el catálogo completo de **973 cuentas PUC** con `--with-puc`.
- **Catálogo de cuentas jerárquico** — árbol ilimitado de subcuentas auxiliares con naturaleza (débito/crédito) heredada de la clase.
- **Periodos y ejercicios fiscales** — apertura, cierre y reapertura de periodos; cierre de ejercicio con asiento de resultado automático.
- **Tipos y numeraciones de comprobante** — CI, CE, CD, CA, CO, NC, CIE con secuencias independientes por tipo.
- **Centros de costo** — asignables por línea de comprobante.
- **Terceros polimórficos** — cualquier modelo de tu aplicación puede actuar como tercero (proveedor, cliente, empleado, etc.) sin necesidad de duplicar datos.
- **UI Blade + API REST** — ambas capas pueden activarse de forma independiente.
- **Multi-tenancy opcional** — filtrado automático por `tenant_id` vía Global Scope; desactivado por defecto.
- **Autorización vía Gates** — el paquete no impone roles; tú defines quién puede entrar, registrar comprobantes, ver reportes o administrar el módulo.
- **Modelos intercambiables** — cualquier modelo del paquete puede reemplazarse por uno propio desde la configuración.
- **7 reportes contables** — Libro Diario, Mayor General, Balance de Comprobación, Estado de Situación Financiera, Estado de Resultados, Mayor por Tercero y Reporte por Centro de Costo.

## Requisitos

| Dependencia | Versión |
|---|---|
| PHP | ^8.2 |
| Laravel | ^11.0 \| ^12.0 |
| Alpine.js | ^3.x (CDN, incluido en la UI) |

## Instalación

```bash
composer require numerar/contable
```

Ejecuta el comando de instalación:

```bash
php artisan contable:install
```

Esto publica `config/contable.php`, corre las migraciones y siembra las 9 clases PUC y los tipos de comprobante por defecto.

### Opciones del instalador

```bash
# Cargar el catálogo completo PUC (973 cuentas)
php artisan contable:install --with-puc

# Reinstalar desde cero (¡destructivo: elimina las tablas!)
php artisan contable:install --fresh

# Instalar sin datos iniciales
php artisan contable:install --no-seed
```

### Publicar recursos individualmente

```bash
# Configuración
php artisan vendor:publish --tag=contable-config

# Migraciones
php artisan vendor:publish --tag=contable-migrations

# Vistas Blade (para personalizar la UI)
php artisan vendor:publish --tag=contable-views

# Assets CSS (se copia automáticamente con contable:install)
php artisan vendor:publish --tag=contable-assets --force
```

## Configuración rápida

El archivo `config/contable.php` cubre todas las opciones. Los ajustes más comunes:

### Middleware y prefijos de ruta

```php
'web_prefix'     => 'contabilidad',           // /contabilidad/...
'web_middleware' => ['web', 'auth'],           // añade auth para proteger la UI
'api_prefix'     => 'api/contabilidad',
'api_middleware' => ['api', 'auth:sanctum'],
```

### Activar/desactivar capas

```php
'features' => [
    'web' => true,   // interfaz Blade
    'api' => true,   // API REST JSON
],
```

## Autorización

El paquete define cuatro gates. Si no los defines tú, **todos pasan** (modo desarrollo). En producción, decláralos en tu `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('contable.access',  fn($u) => $u->hasRole(['admin', 'contador']));
Gate::define('contable.entries', fn($u) => $u->hasRole(['admin', 'contador']));
Gate::define('contable.reports', fn($u) => $u->hasRole(['admin', 'contador', 'auditor']));
Gate::define('contable.admin',   fn($u) => $u->hasRole('admin'));
```

| Gate | Protege |
|---|---|
| `contable.access` | Acceso general al módulo |
| `contable.entries` | Crear, editar y anular comprobantes |
| `contable.reports` | Ver reportes contables |
| `contable.admin` | Cerrar periodos, configurar ejercicios |

## Multi-tenancy

Desactivado por defecto. Para activarlo:

```php
// config/contable.php
'tenancy' => [
    'enabled' => true,
    'column'  => 'tenant_id',
],
```

Registra el resolver en tu `AppServiceProvider::boot()`:

```php
use Numerar\Contable\Facades\Contable;

Contable::resolveTenantUsing(fn() => auth()->user()?->company_id);
```

Todas las tablas tienen `tenant_id BIGINT DEFAULT 0`. El valor `0` actúa como centinela para modo single-tenant, evitando problemas con índices únicos en MySQL.

## Terceros polimórficos

Las líneas de comprobante aceptan cualquier modelo como tercero. Por defecto apunta al modelo `Tercero` del paquete; puedes agregar los tuyos:

```php
// config/contable.php
'terceros' => [
    [
        'model'             => \App\Models\Proveedor::class,
        'label'             => 'Proveedores',
        'display_attribute' => 'razon_social',
        'search_attributes' => ['razon_social', 'nit'],
    ],
    [
        'model'             => \App\Models\Cliente::class,
        'label'             => 'Clientes',
        'display_attribute' => 'nombre',
        'search_attributes' => ['nombre', 'identificacion'],
    ],
],
```

El selector en la UI agrupa las opciones por tipo de modelo automáticamente.

## Modelos intercambiables

Extiende cualquier modelo del paquete y regístralo en la config:

```php
// app/Models/MiCuenta.php
class MiCuenta extends \Numerar\Contable\Models\Account
{
    // tus personalizaciones
}

// config/contable.php
'models' => [
    'account' => \App\Models\MiCuenta::class,
    // el resto sigue igual...
],
```

## Crear comprobantes por código

Usa la fachada `Contable` o inyecta `EntryService` directamente. Todas las operaciones corren dentro de una transacción y lanzan excepciones tipadas ante cualquier violación de reglas contables.

### Requisito previo: periodo abierto

El comprobante se asocia automáticamente al periodo contable cuya fecha de inicio/fin contenga `date`. Si el periodo no existe o está cerrado, se lanza `PeriodClosedException`.

```php
use Numerar\Contable\Facades\Contable;

// Crear el periodo si aún no existe
Contable::createPeriod([
    'year'       => 2025,
    'month'      => 1,
    'start_date' => '2025-01-01',
    'end_date'   => '2025-01-31',
]);
```

### Registrar un comprobante

```php
$entry = Contable::createEntry([
    'entry_type'  => 'CI',          // código del tipo de comprobante
    'date'        => '2025-01-15',
    'description' => 'Venta de mercancía al contado',
    'created_by'  => auth()->id(),  // opcional

    'lines' => [
        [
            'account_id'  => 111,   // id de la cuenta Caja (1105)
            'debit'       => 1190000,
            'credit'      => 0,
            'description' => 'Recaudo en efectivo',
        ],
        [
            'account_id'  => 340,   // id de la cuenta IVA generado (240805)
            'debit'       => 0,
            'credit'      => 190000,
            'description' => 'IVA 19%',
        ],
        [
            'account_id'  => 210,   // id de la cuenta Ventas (4101)
            'debit'       => 0,
            'credit'      => 1000000,
            'description' => 'Venta mercancía',
            // opcional: tercero polimórfico
            'third_party_type' => \App\Models\Cliente::class,
            'third_party_id'   => 7,
            // opcional: centro de costo
            'cost_center_id'   => 2,
        ],
    ],
]);
// $entry->entry_number  → "CI-0001/2025"
// $entry->status        → EntryStatus::POSTED
```

El servicio valida que `sum(debit) === sum(credit)` antes de confirmar la transacción. Si no cuadra, lanza `UnbalancedEntryException`.

### Editar un comprobante

```php
Contable::updateEntry($entry, [
    'description' => 'Descripción corregida',
    'lines'       => [...],   // reemplaza todas las líneas
]);
```

### Anular un comprobante

```php
Contable::voidEntry($entry);
// o por id:
Contable::voidEntry(42);
```

Los comprobantes anulados quedan con `status = VOIDED` y no pueden editarse. Sus movimientos dejan de aparecer en reportes.

### Excepciones tipadas

| Excepción | Cuándo se lanza |
|---|---|
| `AccountingException` | Regla de negocio general (cuenta inactiva, tipo de comprobante inexistente, etc.) |
| `UnbalancedEntryException` | `sum(débitos) ≠ sum(créditos)` |
| `PeriodClosedException` | El periodo contable está cerrado |

```php
use Numerar\Contable\Exceptions\UnbalancedEntryException;
use Numerar\Contable\Exceptions\PeriodClosedException;

try {
    Contable::createEntry([...]);
} catch (PeriodClosedException $e) {
    // abrir el periodo primero
} catch (UnbalancedEntryException $e) {
    // $e->getMessage() incluye el valor de la diferencia
}
```

## Reportes disponibles

| Reporte | Ruta web | Endpoint API |
|---|---|---|
| Libro Diario | `/accounting/reports/journal` | `GET /api/accounting/reports/journal` |
| Mayor General | `/accounting/reports/general-ledger` | `GET /api/accounting/reports/general-ledger` |
| Mayor por Tercero | `/accounting/reports/third-party-ledger` | `GET /api/accounting/reports/third-party-ledger` |
| Balance de Comprobación | `/accounting/reports/trial-balance` | `GET /api/accounting/reports/trial-balance` |
| Estado de Situación Financiera | `/accounting/reports/balance-sheet` | `GET /api/accounting/reports/balance-sheet` |
| Estado de Resultados | `/accounting/reports/income-statement` | `GET /api/accounting/reports/income-statement` |
| Centro de Costo | `/accounting/reports/cost-center` | `GET /api/accounting/reports/cost-center` |

> Las rutas usan el prefijo por defecto `accounting`. Cámbialo en `config/contable.php` → `web_prefix`.

## API REST

Todos los recursos cuentan con endpoints JSON bajo el prefijo `api/accounting` (configurable). Ejemplo de rutas:

```
GET    /api/accounting/accounts
POST   /api/accounting/accounts
GET    /api/accounting/accounts/{id}
PUT    /api/accounting/accounts/{id}
DELETE /api/accounting/accounts/{id}

GET    /api/accounting/entries
POST   /api/accounting/entries
PATCH  /api/accounting/entries/{id}/void

GET    /api/accounting/periods
POST   /api/accounting/fiscal-years/{year}/close
```

Las respuestas siguen el formato de Laravel API Resources. Autenticación vía Sanctum por defecto (configurable).

## Estructura del paquete

```
src/
├── ContableServiceProvider.php
├── Console/Commands/InstallCommand.php
├── Database/Seeders/
│   ├── AccountClassSeeder.php      # 9 clases PUC
│   └── EntryTypeSeeder.php         # CI, CE, CD, CA, CO, NC, CIE
├── Enums/                          # AccountNature, AccountType, EntryStatus...
├── Exceptions/                     # UnbalancedEntry, PeriodClosed...
├── Facades/Contable.php
├── Http/
│   ├── Controllers/                # Controladores Blade
│   ├── Controllers/Api/            # Controladores JSON
│   ├── Requests/                   # Form Requests
│   └── Resources/                  # API Resources
├── Models/                         # 10 modelos Eloquent
├── Services/
│   ├── AccountingService.php       # Façade principal, tenant resolver
│   ├── EntryService.php            # Crear, editar, anular comprobantes
│   ├── FiscalYearService.php       # Cierre de ejercicio y resultado
│   └── ReportService.php           # 7 reportes contables
├── Traits/
│   ├── HasTenancy.php
│   └── HasAuditFields.php
├── database/migrations/            # 10 migraciones
└── helpers.php
```

## Licencia

MIT — © Alernal · [juan-rios-dev](https://github.com/juan-rios-dev)
