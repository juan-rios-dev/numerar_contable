<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Traits\HasTenancy;

class Tercero extends Model
{
    use HasTenancy;
    protected $table = 'terceros';

    protected $fillable = [
        'tipo_persona',
        'tipo_documento',
        'numero_documento',
        'digito_verificacion',
        'razon_social',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'es_cliente',
        'es_proveedor',
        'es_empleado',
        'es_otro',
        'responsabilidad_fiscal',
        'municipio',
        'departamento',
        'direccion',
        'email',
        'telefono',
        'celular',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'es_cliente'   => 'boolean',
        'es_proveedor' => 'boolean',
        'es_empleado'  => 'boolean',
        'es_otro'      => 'boolean',
        'activo'       => 'boolean',
    ];

    // ── Accessors ────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        if ($this->tipo_persona === 'JURIDICA') {
            return $this->razon_social ?? '';
        }

        return trim(implode(' ', array_filter([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido,
        ])));
    }

    public function getDocumentoFormateadoAttribute(): string
    {
        $doc = $this->tipo_documento . ' ' . $this->numero_documento;
        if ($this->tipo_documento === 'NIT' && $this->digito_verificacion) {
            $doc .= '-' . $this->digito_verificacion;
        }
        return $doc;
    }

    // ── Relations ────────────────────────────────────────────

    public function entryLines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class, 'third_party_id');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeClientes($q)  { return $q->where('es_cliente', true); }
    public function scopeProveedores($q) { return $q->where('es_proveedor', true); }
    public function scopeEmpleados($q) { return $q->where('es_empleado', true); }
    public function scopeActivos($q)   { return $q->where('activo', true); }
}
