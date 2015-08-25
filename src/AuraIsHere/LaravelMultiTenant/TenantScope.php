<?php namespace AuraIsHere\LaravelMultiTenant;

use Sofa\GlobalScope\GlobalScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;
use AuraIsHere\LaravelMultiTenant\Contracts\LoftyScope;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException;

class TenantScope extends GlobalScope implements ScopeInterface
{
	private $enabled = true;

	/** @var \Illuminate\Database\Eloquent\Model|\AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait */
	private $model;

	/** @var array The tenant scopes currently set */
	protected $tenants = [];

	/**
	 * return tenants
	 *
	 * @return array
	 */
	public function getTenants()
	{
		return $this->tenants;
	}

	/**
	 * Add $tenantColumn => $tenantId to the current tenants array
	 *
	 * @param  string $tenantColumn
	 * @param  mixed  $tenantId
	 *
	 * @return void
	 */
	public function addTenant($tenantColumn, $tenantId)
	{
		$this->enable();

		$this->tenants[$tenantColumn] = $tenantId;
	}

	/**
	 * Remove $tenantColumn => $id from the current tenants array
	 *
	 * @param  string $tenantColumn
	 *
	 * @return boolean
	 */
	public function removeTenant($tenantColumn)
	{
		if ($this->hasTenant($tenantColumn))
		{
			unset($this->tenants[$tenantColumn]);

			return true;
		}

		else
		{
			return false;
		}
	}

	/**
	 * Test whether current tenants include a given tenant
	 *
	 * @param  string $tenantColumn
	 *
	 * @return boolean
	 */
	public function hasTenant($tenantColumn)
	{
		return isset($this->tenants[$tenantColumn]);
	}

	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param Illuminate\Database\Eloquent\Builder $builder
	 * @param Illuminate\Database\Eloquent\Model $model
	 *
	 * @return void
	 */
	public function apply(Builder $builder, Model $model)
	{
		if (! $this->enabled) return;

		// Use whereRaw instead of where to avoid issues with bindings when removing
		foreach ($this->getModelTenants($model) as $tenantColumn => $tenantId)
		{
			$builder->where($tenantColumn, '=', $tenantId);
		}

		$this->extend($builder);
	}

	/**
	 * Add macro function to the builder
	 * @param Illuminate\Database\Eloquent\Builder $builder
	 */
    public function extend(Builder $builder)
    {
        $builder->macro('allTenants', function (Builder $builder) {
            $this->remove($builder, $builder->getModel());
            return $builder;
        });
    }

	public function creating(Model $model)
	{
		// If the model has had the global scope removed, bail
		if (! $model->hasGlobalScope($this)) return;

		// Otherwise, scope the new model
		foreach ($this->getModelTenants($model) as $tenantColumn => $tenantId)
		{
			$model->{$tenantColumn} = $tenantId;
		}
	}

	/**
	 * Return which tenantColumn => tenantId are really in use for this model.
	 *
	 * @param Model $model
	 *
	 * @throws TenantColumnUnknownException
	 * @return array
	 */
	public function getModelTenants(Model $model)
	{
		$modelTenantColumns = $model->getTenantColumns();

		if (! is_array($modelTenantColumns)) $modelTenantColumns = [$modelTenantColumns];

		$modelTenants = [];

		foreach ($modelTenantColumns as $tenantColumn)
		{
			$modelTenants[$tenantColumn] = $this->getTenantId($tenantColumn);
		}

		return $modelTenants;
	}

	/**
	 * @param $tenantColumn
	 *
	 * @throws TenantColumnUnknownException
	 *
	 * @return mixed The id of the tenant
	 */
	public function getTenantId($tenantColumn)
	{
		if (! $this->hasTenant($tenantColumn))
		{
			throw new TenantColumnUnknownException(
				get_class($this->model) . ': tenant column "' . $tenantColumn . '" NOT found in tenants scope "' . json_encode($this->tenants) . '"'
			);
		}

		return $this->tenants[$tenantColumn];
	}

	public function disable()
	{
		$this->enabled = false;
	}

	public function enable()
	{
		$this->enabled = true;
	}
}
