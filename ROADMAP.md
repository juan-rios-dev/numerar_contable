# Koneko Accounting — ROADMAP

Prioridades: **confiabilidad → rendimiento → funcionalidad**.
Un motor contable sin tests ni índices correctos es una deuda técnica que cobra en producción.

---

## FASE 0 — Correcciones críticas (antes de cualquier otra cosa)
> Estos son los "descarches" encontrados en la auditoría. Algunos pueden romper producción silenciosamente.

### 0.1 `account_code` en `createEntry()` — Soporte programático por código
- [ ] El API actual exige `account_id` (entero). Cualquier integración externa (POS, nómina, etc.) debe hacer una query extra por cada línea: `Account::where('code', '41')->first()->id`. Eso es N+1 y frágil.
- [ ] Agregar soporte para `account_code` en los arrays de líneas de `EntryService::create()` / `update()`:
  ```php
  Accounting::createEntry([
      'lines' => [
          ['account_code' => '411005', 'credit' => 150000],
          ['account_code' => '111005', 'debit'  => 150000],
      ]
  ]);
  ```
- [ ] Resolver todos los códigos en un único `Account::whereIn('code', [...])` antes de `syncLines()` — sin N+1
- [ ] Si un código no existe: lanzar `AccountingException` con el código exacto antes de entrar a la transacción
- [ ] `account_id` sigue siendo válido; `account_code` es alternativa, no reemplazo
- [ ] Actualizar `StoreEntryRequest` para aceptar `lines.*.account_code` como alternativa a `lines.*.account_id`

### 0.2 Race condition en la numeración — `AccountingEntrySequence::nextNumber()`
**Impacto: CRÍTICO** — dos requests simultáneas pueden generar el mismo número de comprobante. El índice `unique` lanza un `QueryException` genérico (error 500 en producción).
```php
// Problema: COUNT sin lock — dos hilos leen el mismo valor
$used = AccountingEntry::where('entry_sequence_id', $this->id)
    ->whereYear('date', $year)->count();
```
- [ ] Dentro de `EntryService::create()` (ya usa `DB::transaction()`), bloquear la secuencia antes de leer:
  ```php
  $sequence = AccountingEntrySequence::where('id', $sequence->id)->lockForUpdate()->first();
  ```
- [ ] Capturar `QueryException` con código `23000` (duplicate key) y relanzar como `AccountingException` amigable

### 0.3 `insert()` no setea `tenant_id` — Bug silencioso en multi-tenancy
**Impacto: CRÍTICO** — con tenancy habilitado, todas las líneas se insertan con `tenant_id = 0` porque `AccountingEntryLine::insert()` bypasa el observer de `HasTenancy`. Los reportes filtran por tenant y no encuentran nada.
- [ ] En `EntryService::syncLines()`, agregar en cada record del array: `'tenant_id' => $entry->tenant_id`
- [ ] `$entry->tenant_id` ya está disponible en ese punto (seteado por `HasTenancy::creating()` antes de `syncLines`)

### 0.4 Null crash al reabrir ejercicio fiscal — `FiscalYearService::reopen()`
**Impacto: CRÍTICO** — si el comprobante de cierre fue eliminado manualmente, `$fiscalYear->closingEntry` devuelve `null` y `void(null)` lanza `TypeError`.
```php
// Actual — sin protección:
if ($fiscalYear->closing_entry_id) {
    $this->entries->void($fiscalYear->closingEntry); // puede ser null
}
```
- [ ] Cambiar a:
  ```php
  $closingEntry = $fiscalYear->closingEntry;
  if ($closingEntry) {
      $this->entries->void($closingEntry);
  }
  ```

### 0.5 `openPeriod()` y `closePeriod()` ignoran estado LOCKED
**Impacto: IMPORTANTE** — un período LOCKED (bloqueado por cierre de ejercicio) se puede reabrir o cerrar directamente, corrompiendo la integridad del año fiscal.
- [ ] En `AccountingService::openPeriod()`: verificar `$period->isLocked()` → excepción: _"El período está bloqueado por cierre de ejercicio. Reabre el ejercicio fiscal primero."_
- [ ] En `AccountingService::closePeriod()`: verificar `$period->isLocked()` → excepción similar
- [ ] Verificar que `AccountingPeriod` tenga `isLocked(): bool` (si no, agregar)

### 0.6 Validación de líneas ocurre DESPUÉS del `insert()` — Orden incorrecto
**Impacto: IMPORTANTE** — el flujo actual inserta, valida, y si falla hace rollback. El rollback protege la integridad pero es desperdicio de I/O y el origen del error es confuso.
- [ ] Mover la validación de cada línea ANTES de construir `$records` en `syncLines()` — validar en memoria
- [ ] En `StoreEntryRequest`: agregar regla que al menos `debit > 0` o `credit > 0` (actualmente ambos en 0 pasan la validación HTTP con `min:0`)

### 0.7 `formatNumber()` sin límite — overflow en comprobante 10.000
**Impacto: IMPORTANTE** — `str_pad(..., 4, '0', STR_PAD_LEFT)` produce `CI2026-10000` (5 dígitos) al llegar al comprobante número 10.000, rompiendo el formato fijo.
- [ ] Cambiar el pad a 6 dígitos (hasta 999.999/año) o hacerlo configurable en `config/accounting.php`: `'sequence_pad' => 6`
- [ ] Agregar test: verificar que el número 10.000 se formatea correctamente

### 0.8 `whereYear('date', $year)` — Función sobre columna impide índice
**Impacto: RENDIMIENTO CRÍTICO** — `YEAR(date) = ?` es una función sobre la columna, MySQL no puede usar el índice de `date`, causando full scan en cada numeración.
- [ ] En `AccountingEntrySequence::nextNumber()` reemplazar:
  ```php
  ->whereYear('date', $year)
  // por:
  ->whereBetween('date', ["{$year}-01-01", "{$year}-12-31"])
  ```
- [ ] Revisar todo el codebase por otros `whereYear()` y aplicar la misma corrección

### 0.9 Falta índice en `entry_sequence_id`
**Impacto: RENDIMIENTO** — la query de `nextNumber()` filtra por `entry_sequence_id` pero no tiene índice dedicado. Con 100k+ comprobantes hace full scan.
- [ ] Agregar en la migration de `accounting_entries` (o en una nueva de índices para Fase 2):
  ```php
  $table->index(['entry_sequence_id', 'date']);
  ```

### 0.10 `third_party_id` y `third_party_type` sin validación de consistencia
**Impacto: DATOS INVÁLIDOS** — se puede enviar uno sin el otro. La línea queda con datos polimórficos inconsistentes (ID sin tipo o tipo sin ID).
- [ ] En `StoreEntryRequest` cambiar a `required_with`:
  ```php
  'lines.*.third_party_id'   => ['nullable', 'integer', 'required_with:lines.*.third_party_type'],
  'lines.*.third_party_type' => ['nullable', 'string',  'required_with:lines.*.third_party_id'],
  ```

---

## FASE 1 — Tests (Confiabilidad absoluta)
> Sin esto no se toca producción. El motor contable debe ser el código más probado del sistema.

### 1.1 Infraestructura de tests
- [ ] Configurar `orchestra/testbench` con SQLite in-memory
- [ ] `TestCase` base del paquete con migraciones y seeders mínimos
- [ ] Factories: `AccountFactory`, `EntryFactory`, `TerceroFactory`, `PeriodFactory`

### 1.2 Tests unitarios — Reglas de negocio
- [ ] `AccountNature::netBalance()` — todos los casos (débito, crédito, cero, negativo)
- [ ] `EntryStatus` — transiciones válidas e inválidas
- [ ] `AccountingEntrySequence::formatNumber()` — formatos y año correcto
- [ ] `AccountingPeriod::containsDate()` — borde inicio, fin y fuera de rango
- [ ] `AccountingEntry::isBalanced()` / `difference()` — con y sin decimales

### 1.3 Tests de integración — EntryService
- [ ] Crear comprobante válido → se guarda con estado POSTED
- [ ] Crear comprobante con `account_code` en lugar de `account_id` → resuelve IDs correctamente
- [ ] Crear comprobante con `account_code` inexistente → lanza `AccountingException` antes del INSERT
- [ ] Comprobante desbalanceado → lanza `UnbalancedEntryException`
- [ ] Menos de 2 líneas → lanza `AccountingException`
- [ ] Línea con `debit=0, credit=0` → lanza `AccountingException`
- [ ] Cuenta inactiva en una línea → lanza `AccountingException`
- [ ] Fecha fuera del período → lanza `AccountingException`
- [ ] Período cerrado → lanza `PeriodClosedException`
- [ ] Período inexistente → lanza `AccountingException`
- [ ] Dos comprobantes creados concurrentemente → numeración sin duplicados (simular con dos transactions)
- [ ] `tenant_id` en líneas iguala al `tenant_id` del comprobante (regresión del bug 0.3)
- [ ] Anular comprobante → estado VOIDED, no se puede editar
- [ ] Anular comprobante ya anulado → lanza excepción
- [ ] Eliminar comprobante VOIDED → se elimina con sus líneas
- [ ] Eliminar comprobante POSTED → lanza excepción (protección)
- [ ] Actualizar comprobante → líneas se reemplazan completas (syncLines)
- [ ] Numeración automática → incrementa correctamente por tipo y año

### 1.4 Tests de integración — Reportes
- [ ] `journal()` → agrupa líneas por comprobante, totales correctos
- [ ] `generalLedger()` → saldo inicial + movimientos + saldo final cuadran
- [ ] `trialBalance()` → suma débitos = suma créditos (partida doble)
- [ ] `balanceSheet()` → Activo = Pasivo + Patrimonio + Resultado
- [ ] `incomeStatement()` → utilidad neta = ingresos - costos - gastos
- [ ] `costCenter()` → filtra correctamente por centro de costo
- [ ] `thirdPartyLedger()` → saldo anterior + movimientos + saldo final cuadran
- [ ] Reportes con rango vacío (sin movimientos) → retornan estructura válida sin errores

### 1.5 Tests de integración — Períodos y Cierre fiscal
- [ ] Crear período → se puede crear solo uno por mes/año
- [ ] Cerrar período → no se pueden registrar nuevos comprobantes
- [ ] Reabrir período → se pueden volver a registrar
- [ ] Intentar reabrir período LOCKED → lanza excepción (regresión bug 0.5)
- [ ] Intentar cerrar período LOCKED → lanza excepción (regresión bug 0.5)
- [ ] Cierre de ejercicio fiscal → genera comprobante de cierre, saldos pasan a clase 3
- [ ] No se puede cerrar ejercicio con períodos abiertos
- [ ] Reabrir ejercicio fiscal → anula comprobante de cierre
- [ ] Reabrir ejercicio fiscal con `closing_entry_id` huérfano → no explota (regresión bug 0.4)

### 1.6 Tests de integración — Terceros y polimorfía
- [ ] MorphTo eager loading resuelve el tercero correcto (regresión del bug fix)
- [ ] `getThirdPartyNameAttribute()` con modelo en config → retorna nombre correcto
- [ ] `getThirdPartyNameAttribute()` sin config → retorna fallback sin explotar

---

## FASE 2 — Rendimiento y Escalabilidad (BigData)
> Las tablas de líneas de comprobante crecen 5-20 filas por asiento. Con 1.000 comprobantes/mes = 1M filas/año. Hay que estar listos.

### 2.1 Auditoría de índices (CRÍTICO)
- [ ] Revisar migration `accounting_entries`: agregar índice compuesto `(status, date)`
- [ ] Revisar migration `accounting_entry_lines`: agregar índices:
  - `(entry_id)` — ya existe como FK, verificar
  - `(account_id, entry_id)` — para aggregateSums y generalLedger
  - `(third_party_type, third_party_id)` — para thirdPartyLedger
  - `(cost_center_id)` — para costCenter report
- [ ] Revisar migration `accounts`: índice en `(parent_id)` para árbol jerárquico
- [ ] Ejecutar `EXPLAIN` / `EXPLAIN ANALYZE` sobre las queries de cada reporte con dataset grande
- [ ] Crear migration adicional `add_performance_indexes` con todos los índices faltantes

### 2.2 Optimización de queries críticas
- [ ] `aggregateSums()` — el método más llamado en el sistema; analizar plan de ejecución con 1M+ filas
- [ ] `thirdPartyLedger()` — actualmente corre 2 queries + N por tipo de tercero; evaluar si se puede consolidar
- [ ] `trialBalance()` — carga árbol completo de cuentas con eager loading; medir con PUC completo (973 cuentas)
- [ ] `buildTrialBalanceNodes()` — recursión PHP sobre colección; evaluar límite práctico de profundidad
- [ ] `journal()` — sin paginación, puede traer miles de filas; agregar paginación o límite configurable
- [ ] `thirdPartyLedger()` — mismo problema; paginación o chunk por cuenta

### 2.3 Paginación y chunking
- [ ] Libro Diario: paginación configurable (default 100 comprobantes por página)
- [ ] Auxiliar por Tercero: paginación por cuenta (no traer todo en una sola respuesta)
- [ ] Libro Mayor: ya es por cuenta específica, evaluar si necesita límite de filas
- [ ] `PucSeeder` — ya usa inserción en lotes; validar que opere correctamente en producción con transacciones largas

### 2.4 Cache de reportes (configurable)
- [ ] Definir en config: `'cache' => ['enabled' => false, 'ttl' => 300]`
- [ ] Balance General y Estado de Resultados: candidatos a cache (cambian poco intraday)
- [ ] Balance de Prueba: cache por rango de fechas
- [ ] Invalidación de cache al crear/anular comprobante

---

## FASE 3 — Funcionalidad (Dinamismo y completitud)

### 3.1 Retenciones (alta prioridad en Colombia)
- [ ] Tabla `retention_types` (fuente, ICA, IVA, timbre)
- [ ] Relacionar retenciones con líneas de comprobante
- [ ] Reporte de retenciones practicadas y sufridas por período
- [ ] Certificados de retención (exportable)

### 3.2 Importación masiva
- [ ] Importar comprobantes desde Excel/CSV (plantilla descargable)
- [ ] Validación fila por fila antes de insertar (mostrar errores agrupados)
- [ ] Jobs en cola para importaciones grandes (> 500 comprobantes)
- [ ] Rollback completo si alguna fila falla

### 3.3 Exportación y documentos
- [ ] PDF de comprobante individual (vista de impresión)
- [ ] PDF/Excel de cada reporte
- [ ] Exportar Balance de Prueba en formato SIIF/NIIF (estándar DIAN)

### 3.4 Conciliación bancaria
- [ ] Tabla `bank_transactions` (extracto importado)
- [ ] Algoritmo de matching automático contra cuentas bancarias
- [ ] Vista de conciliación: movimientos conciliados vs. pendientes
- [ ] Reporte de partidas conciliatorias

### 3.5 Mejoras menores pero importantes
- [ ] Búsqueda full-text en comprobantes (por descripción, número, tercero)
- [ ] Duplicar comprobante (clonar estructura para asientos recurrentes)
- [ ] Comprobantes recurrentes (programar asiento mensual automático)
- [ ] Notas / comentarios en líneas de comprobante
- [ ] Historial de cambios (auditoría: quién editó qué y cuándo)

---

## Deuda técnica conocida

- [ ] `buildTerceroOptions()` duplicado en `EntryController` y `ReportController` → extraer a un trait o service
- [ ] `ReportApiController` — verificar que todos los endpoints cubran los mismos reportes que el web controller
- [ ] `AccountingService` crece; evaluar separar en sub-services cuando supere ~400 líneas
- [ ] Validar comportamiento de `use_terceros_table => false` instalando en proyecto limpio (pendiente prueba real)

---

## Notas de arquitectura

- **No romper la API pública** (`Accounting::createEntry()`, `Accounting::journal()`, etc.) — los consumidores del paquete dependen de ella.
- Los índices de la Fase 2 deben ir en una **migration separada** para no alterar las migraciones originales del esquema.
- El cache de la Fase 2 debe ser **opt-in** (desactivado por defecto) para no crear dependencia de Redis en instalaciones simples.
