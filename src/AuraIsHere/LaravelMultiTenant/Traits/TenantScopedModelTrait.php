<?php namespace AuraIsHere\LaravelMultiTenant\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\TenantScope;
use AuraIsHere\LaravelMultiTenant\Facades\TenantScopeFacade;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException;

/**
 * Class TenantScopedModelTrait
 *
 * @package AuraIsHere\LaravelMultiTenant
 *
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\ScopeInterface $scope)
 * @method static void creating(callable $callback)
 */
trait TenantScopedModelTrait {

	public static function bootTenantScopedModelTrait()
	{
		$tenantScope = App::make("AuraIsHere\LaravelMultiTenant\TenantScope");

		// Add the global scope that will handle all operations except create()
		static::addGlobalScope($tenantScope);

		// Add an observer that will automatically add the tenant id when create()-ing
		static::creating(function (Model $model) use ($tenantScope)
		{
			$tenantScope->creating($model);
		});
	}

	/**
	 * Get the name of the "tenant id" column.
	 *
	 * @return string
	 */
	public function getTenantColumns()
	{
		return isset($this->tenantColumns) ? $this->tenantColumns : Config::get('laravel-multi-tenant::default_tenant_columns');
	}

	/**
	 * Override the default findOrFail method so that we can rethrow a more useful exception.
	 * Otherwise it can be very confusing why queries don't work because of tenant scoping issues.
	 *
	 * @param       $id
	 * @param array $columns
	 *
	 * @throws TenantModelNotFoundException
	 */
	public static function findOrFail($id, $columns = array('*'))
	{
		try
		{
			return parent::findOrFail($id, $columns);
		}

		catch (ModelNotFoundException $e)
		{
			throw with(new TenantModelNotFoundException)->setModel(get_called_class());
		}
	}
} 
