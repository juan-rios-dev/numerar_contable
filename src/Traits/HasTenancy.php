<?php

namespace Numerar\Contable\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasTenancy
{
    public static function bootHasTenancy(): void
    {
        if (! config('contable.tenancy.enabled', false)) {
            return;
        }

        static::addGlobalScope('tenant', function (Builder $query) {
            $tenantId = app('contable')->resolveTenant();

            if ($tenantId !== null) {
                $column = config('contable.tenancy.column', 'tenant_id');
                $query->where($query->getModel()->getTable() . '.' . $column, $tenantId);
            }
        });

        static::creating(function ($model) {
            $tenantId = app('contable')->resolveTenant();
            $column   = config('contable.tenancy.column', 'tenant_id');

            if (empty($model->{$column})) {
                $model->{$column} = $tenantId ?? 0;
            }
        });
    }
}
