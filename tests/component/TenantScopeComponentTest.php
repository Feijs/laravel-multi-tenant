<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Builder;
use AuraIsHere\LaravelMultiTenant\TenantScope;

class TenantScopeComponentTest extends ComponentTestCase 
{
	/**
	 * To avoid tenant scope propagating to other tests
     * @runInSeparateProcess
     */
	public function testSqlInjectionProtection()
	{
		$this->tenantScope->addTenant('tenant_id', '5 or 1 == 1');
		$result = $this->model->newQuery()->toSql();

		$this->assertNotContains('1 == 1', $result);
	}

	/**
	 * To avoid tenant scope propagating to other tests
     * @runInSeparateProcess
     */
	public function testScopeRemoval()
	{
		$this->tenantScope->addTenant('tenant_id', 5);

		//No scopes applied
		$empty_builder = $this->model->newQueryWithoutScopes();
		$empty_builder = $this->getTestOuterQuery($empty_builder);
		$empty_builder = $this->getTestSubQuery($empty_builder);

		//Applies and then removes scope
		$processed_builder = $this->model->newQueryWithoutScope($this->tenantScope);
		$processed_builder = $this->getTestOuterQuery($processed_builder);
		$processed_builder = $this->getTestSubQuery($processed_builder);

		$empty_query = $empty_builder->getQuery();
		$processed_query = $processed_builder->getQuery();

		$this->assertEquals($empty_query->getRawBindings(), $processed_query->getRawBindings());
	}
}
