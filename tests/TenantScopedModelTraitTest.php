<?php

use Mockery as m;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;

class TenantScopedModelTraitTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testGetTenantColumns()
	{
		$model = m::mock('TenantScopedModelStub');
		$model->shouldDeferMissing();

		Config::shouldReceive('get')->with('laravel-multi-tenant::default_tenant_columns')
			  ->once()->andReturn('company_id');
		$this->assertEquals('company_id', $model->getTenantColumns());

		$model->tenantColumns = 'tenant_id';
		$this->assertEquals('tenant_id', $model->getTenantColumns());

	}

	/**
	 * @expectedException \AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException
	 */
	public function testFindOrFailThrowsTenantException()
	{
		TenantScopedModelStub::findOrFail(1, []);
	}
}

class TenantScopedModelStub extends ParentModel {

	use TenantScopedModelTrait;

	public function getTable()
	{
		return 'table';
	}
}

class ParentModel {

	public static function findOrFail($id, $columns)
	{
		throw new ModelNotFoundException;
	}
}
