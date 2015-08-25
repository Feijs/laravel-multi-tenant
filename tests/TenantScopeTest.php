<?php

use AuraIsHere\LaravelMultiTenant\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Mockery as m;

class TenantScopeTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
		parent::tearDown();
	}

	public function testAccessors()
	{
		$tenantScope = new TenantScope();

		$tenantScope->addTenant('column', 1);

		$tenants = $tenantScope->getTenants();
		$this->assertEquals(['column' => 1], $tenants);

		$this->assertTrue($tenantScope->hasTenant('column'));

		$tenantScope->removeTenant('column');

		$tenants = $tenantScope->getTenants();
		$this->assertEquals([], $tenants);
	}

	public function testApply()
	{
		$scope   = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$model   = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->once()->with($model)->andReturn(['column' => 1]);

		$builder->shouldReceive('where')->once()->with("column",  "=", "1");
		$builder->shouldReceive('macro')->once()->with("allTenants", m::type('Closure'));

		$scope->apply($builder, $model);
	}

	public function testCreating()
	{
		$scope = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$model = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->with($model)->andReturn(['column' => 1]);

		$model->shouldDeferMissing();
		$model->shouldReceive('hasGlobalScope')->andReturn(true);

		$scope->creating($model);

		$this->assertEquals(1, $model->column);
	}

	public function testGetModelTenants()
	{
		$scope = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$model = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getTenantId')->once()->andReturn(1);

		$model->shouldReceive('getTenantColumns')->once()->andReturn(['column']);

		$modelTenants = $scope->getModelTenants($model);

		$this->assertEquals(['column' => 1], $modelTenants);
	}

	/**
	 * @expectedException \AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException
	 */
	public function testGetTenantIdThrowsException()
	{
		$scope = new TenantScope;

		$scope->getTenantId('column');
	}

	public function testDisable()
	{
		$scope   = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$model   = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->with($model)->andReturn(['column' => 1])->never();

		$builder->shouldReceive('getModel')->andReturn($model)->never();
		$builder->shouldReceive('where')->with("column",  "=", "1")->never();

		$model->shouldReceive('getTenantWhereClause')->with('column', 1)->andReturn("table.column = '1'")->never();

		$scope->disable();
		$scope->apply($builder, $model);
	}

	public function testAllTenantsExtension()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldDeferMissing();
        
        $scope = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('allTenants');

        $givenBuilder = m::mock('Illuminate\Database\Eloquent\Builder');
        $givenBuilder->shouldReceive('getModel')->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        
        $scope->shouldReceive('remove')->once()->with($givenBuilder, $model);

        $result = $callback($givenBuilder);
        $this->assertEquals($givenBuilder, $result);
    }
}
