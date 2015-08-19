<?php

use Mockery as m;
use Illuminate\Support\Facades\Config;
use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TenantScopedModelTraitTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

	public function testAllTenants()
	{
		$model = m::mock(new TenantScopedModelStub);

		$this->assertEquals('newQueryWithoutScopeStub', $model::allTenants());
	}

	public function testGetTenantColumns()
	{
		$model = m::mock('TenantScopedModelStub');
		$model->shouldDeferMissing();

		Config::shouldReceive('get')->with('tenant.default_tenant_columns')
			  ->once()->andReturn('company_id');
		$this->assertEquals('company_id', $model->getTenantColumns());

		$model->tenantColumns = 'tenant_id';
		$this->assertEquals('tenant_id', $model->getTenantColumns());

	}

    public function testGetTenantWhereClause()
    {
        $model = m::mock('TenantScopedModelStub');
        $model->shouldDeferMissing();

        $whereClause = $model->getTenantWhereClause('column', 1);

        $this->assertEquals("table.column = '1'", $whereClause);
    }

    /**
     * @expectedException \AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException
     */
    public function testFindOrFailThrowsTenantException()
    {
        TenantScopedModelStub::findOrFail(1, []);
    }
}

class TenantScopedModelStub extends ParentModel
{
    use TenantScopedModelTrait;

    public function getTable()
    {
        return 'table';
    }

	public static function newQueryWithoutScope(\Illuminate\Database\Eloquent\ScopeInterface $scope) 
	{ 
		return "newQueryWithoutScopeStub"; 
	}
}

class ParentModel
{
    public static function findOrFail($id, $columns)
    {
        throw new ModelNotFoundException();
    }

    public static function query()
    {
        throw new ModelNotFoundException();
    }
}
