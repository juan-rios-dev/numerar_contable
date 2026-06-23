<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Funcionalidades activas
    |--------------------------------------------------------------------------
    | Activa o desactiva la capa web (Blade) y/o la API JSON de forma
    | independiente. Ambas pueden estar activas simultáneamente.
    */
    'features' => [
        'web' => true,
        'api' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas web (Blade UI)
    |--------------------------------------------------------------------------
    */
    'web_prefix'     => 'accounting',
    'web_middleware' => ['web'],
    'web_as'         => 'contable.',

    /*
    |--------------------------------------------------------------------------
    | Rutas API
    |--------------------------------------------------------------------------
    */
    'api_prefix'     => 'api/accounting',
    'api_middleware' => ['api'],
    'api_as'         => 'api.contable.',

    /*
    |--------------------------------------------------------------------------
    | Gates de autorización
    |--------------------------------------------------------------------------
    | El paquete verifica estos gates antes de cada acción.
    | Si no defines ninguno en tu AuthServiceProvider, todos pasan (true).
    |
    | Ejemplo en tu AuthServiceProvider::boot():
    |   Gate::define('contable.access',  fn($u) => $u->hasRole(['admin','contador']));
    |   Gate::define('contable.entries', fn($u) => $u->hasRole(['admin','contador']));
    |   Gate::define('contable.reports', fn($u) => $u->hasRole(['admin','contador','auditor']));
    |   Gate::define('contable.admin',   fn($u) => $u->hasRole('admin'));
    */
    'gates' => [
        'access'  => 'contable.access',   // entrar al módulo
        'entries' => 'contable.entries',  // crear/editar/anular comprobantes
        'reports' => 'contable.reports',  // ver reportes
        'admin'   => 'contable.admin',    // cerrar periodos, configuración
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy
    |--------------------------------------------------------------------------
    | Cuando está habilitado, todos los modelos filtran automáticamente
    | por tenant_id usando un Global Scope.
    |
    | Debes registrar el resolver en tu AppServiceProvider::boot():
    |   \Numerar\Contable\Facades\Accounting::resolveTenantUsing(
    |       fn() => auth()->user()?->company_id
    |   );
    |
    | 'type' acepta 'unsignedBigInteger' o 'uuid'
    */
    'tenancy' => [
        'enabled' => false,
        'column'  => 'tenant_id',
        'type'    => 'unsignedBigInteger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modelos swappeables
    |--------------------------------------------------------------------------
    | Reemplaza cualquier modelo del paquete por uno propio.
    | Tu modelo debe extender el del paquete:
    |   class Account extends \Numerar\Contable\Models\Account { ... }
    */
    'models' => [
        'account'        => \Numerar\Contable\Models\Account::class,
        'account_class'  => \Numerar\Contable\Models\AccountClass::class,
        'entry'          => \Numerar\Contable\Models\AccountingEntry::class,
        'entry_line'     => \Numerar\Contable\Models\AccountingEntryLine::class,
        'entry_type'     => \Numerar\Contable\Models\AccountingEntryType::class,
        'entry_sequence' => \Numerar\Contable\Models\AccountingEntrySequence::class,
        'period'         => \Numerar\Contable\Models\AccountingPeriod::class,
        'fiscal_year'    => \Numerar\Contable\Models\AccountingFiscalYear::class,
        'cost_center'    => \Numerar\Contable\Models\CostCenter::class,
        'tercero'        => \Numerar\Contable\Models\Tercero::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Terceros (entidades externas)
    |--------------------------------------------------------------------------
    | La relación con terceros es polimórfica: cualquier modelo de tu proyecto
    | puede actuar como tercero en las líneas de comprobante.
    |
    | 'use_terceros_table'
    |   true  → el paquete crea y usa su propia tabla `terceros` con el modelo
    |            \Numerar\Contable\Models\Tercero incluido abajo.
    |   false → no se crea la tabla `terceros`. Usa tus propios modelos en el
    |            array de abajo (p.ej. App\Models\Customer, App\Models\Employee).
    |
    | Cada entrada del array define cómo mostrar el modelo en la UI:
    |   'model'             => FQCN del modelo (tu tabla, tu modelo)
    |   'label'             => nombre legible en el selector del comprobante
    |   'display_attribute' => atributo (o accessor) que muestra el nombre
    |   'search_attributes' => atributos para búsqueda en el selector
    |
    | Ejemplo con modelos propios:
    |   'use_terceros_table' => false,
    |   'terceros' => [
    |       [
    |           'model'              => \App\Models\Customer::class,
    |           'label'              => 'Cliente',
    |           'display_attribute'  => 'full_name',
    |           'search_attributes'  => ['full_name', 'nit', 'email'],
    |       ],
    |       [
    |           'model'              => \App\Models\Employee::class,
    |           'label'              => 'Empleado',
    |           'display_attribute'  => 'name',
    |           'search_attributes'  => ['name', 'cedula'],
    |       ],
    |   ],
    */
    'use_terceros_table' => true,

    'terceros' => [
        [
            'model'              => \Numerar\Contable\Models\Tercero::class,
            'label'              => 'Tercero',
            'display_attribute'  => 'nombre_completo',
            'search_attributes'  => ['razon_social', 'primer_nombre', 'primer_apellido', 'nit', 'cedula'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moneda y formato numérico
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'symbol'    => '$',
        'code'      => 'COP',
        'decimals'  => 0,
        'thousands' => '.',
        'decimal'   => ',',
    ],

];
