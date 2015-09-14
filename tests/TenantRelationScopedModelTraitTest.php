<?php

use AuraIsHere\LaravelMultiTenant\Traits\TenantRelationScopedModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery as m;

class TenantRelationScopedModelTraitTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAddTenantWhereClause()
    {
        $model = m::mock('TenantRelationScopedModelStub');
        $model->shouldDeferMissing();

        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('whereHas')->once()->with('tenants', m::type('Closure'))->andReturn($builder);

        $model->addTenantClause($builder, 'column', 3);
    }
}

class TenantRelationScopedModelStub extends Model
{
    use TenantRelationScopedModelTrait;

    protected $tenant_relation = 'tenants';
    
    public function tenants() 
    {
        $this->belongsToMany('Tenant');
    }
}
