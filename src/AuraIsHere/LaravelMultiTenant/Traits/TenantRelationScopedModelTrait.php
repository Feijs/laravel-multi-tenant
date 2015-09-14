<?php

namespace AuraIsHere\LaravelMultiTenant\Traits;

use Illuminate\Support\Facades\App;

/**
 * Class TenantRelationScopedModelTrait.
 *
 */
trait TenantRelationScopedModelTrait
{
    use TenantScopedModelTrait;

    public static function bootTenantScopedModelTrait()
    {
        $tenantScope = App::make("AuraIsHere\LaravelMultiTenant\TenantScope");

        // Add the global scope that will handle all operations except create()
        static::addGlobalScope($tenantScope);
    }

    /**
     * Add a Tenant clause
     *
     * @param Illuminate\Database\Eloquent\Builder $builder
     * @param string $tenantColumn
     * @param int $tenantId
     *
     * @return Illuminate\Database\Eloquent\Builder $builder
     */
    public function addTenantClause($builder, $tenantColumn, $tenantId)
    {
        $tenantKey = $this->{$this->tenant_relation}()->getOtherKey();

        return $builder->whereHas($this->tenant_relation, function($q) use ($tenantKey, $tenantId)
                        {
                            $q->where($tenantKey, '=', $tenantId);
                        });
    }

}
