<?php

use Mockery as m;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use AuraIsHere\LaravelMultiTenant\TenantScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;

class TenantScopedModelTraitTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
		parent::tearDown();
	}

	public function testGetTenantColumns()
	{
		$model = m::mock('TenantScopedModelStub');
		$model->shouldDeferMissing();

		Config::shouldReceive('get')->with('laravel-multi-tenant::default_tenant_columns')
			  ->once()->andReturn(['company_id']);
		$this->assertEquals(['company_id'], $model->getTenantColumns());

		$model->tenantColumns = ['tenant_id'];
		$this->assertEquals(['tenant_id'], $model->getTenantColumns());
	}

	/**
	 * @expectedException \AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException
	 */
	public function testFindOrFailThrowsTenantException()
	{
		TenantScopedModelStub::findOrFail(1, []);
	}

	/**
	 * @expectedException AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException
	 */
	public function testNewQueryReturnsTenantQueryBuilder()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
        $processor = m::mock('Illuminate\Database\Query\Processors\Processor');

        $conn->shouldReceive('getQueryGrammar')->twice()->andReturn($grammar);
        $conn->shouldReceive('getPostProcessor')->twice()->andReturn($processor);
        TenantScopedModelStub::setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));
        $resolver->shouldReceive('connection')->andReturn($conn);
        
        App::shouldReceive('make')->once()->with("AuraIsHere\LaravelMultiTenant\TenantScope")->andReturn(new TenantScope);

        $model = new TenantScopedModelStub;
        $builder = $model->newQuery();
        
        $this->assertInstanceOf('AuraIsHere\LaravelMultiTenant\TenantQueryBuilder', $builder);
    }
}

class ParentModel extends Model {

	public static function findOrFail($id, $columns)
	{
		throw new ModelNotFoundException;
	}
}

class TenantScopedModelStub extends ParentModel {

	use TenantScopedModelTrait;

	public function getTable()
	{
		return 'table';
	}
}


