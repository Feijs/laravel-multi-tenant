<?php

use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;

class TenantQueryNestingTest extends ComponentTestCase
{
    /** 
     * Test a simple query in which no nesting should occur.
     */
    public function testSimpleQuery()
    {
        //Reference query
        $nestedQuery = $this->model->newQueryWithoutScopes()->where('tenant_id', '=', 1);

        //Query to be tested
        $tenantQuery = $this->model->newQuery();

        $this->assertEquals($nestedQuery->getQuery()->getRawBindings(), $tenantQuery->getQuery()->getRawBindings());
        $this->assertEquals($nestedQuery->toSql(), $tenantQuery->toSql());
    }

    /** 
     * Test shows the original issue in which 'or where' clauses are not nested
     * and tests whether it is resolved (meaning: output sql matches a reference query).
     */
    public function testNestingQuery()
    {
        //Reference query
        $nestedQuery = $this->model->newQueryWithoutScopes()->where('tenant_id', '=', 1);
        $nestedQuery = $this->getTestOuterQuery($nestedQuery);
        $nestedQuery->where(function ($subq) {
            $this->getTestSubQuery($subq);
        });

        //Query to be tested
        $tenantQuery = $this->getTestOuterQuery($this->model->newQuery());
        $tenantQuery = $this->getTestSubQuery($tenantQuery);

        $this->assertEquals($nestedQuery->getQuery()->getRawBindings(), $tenantQuery->getQuery()->getRawBindings());
        $this->assertEquals($nestedQuery->toSql(), $tenantQuery->toSql());
    }

    /** 
     * Tested seperately due to relation queries now using hash
     * as temporary table names, so can only verify string lengths
     * and bindings match.
     */
    public function testWhereHasNestingQuery()
    {
        //Reference query
        $nestedQuery = $this->model->newQueryWithoutScopes()->where('tenant_id', '=', 1);
        $nestedQuery = $this->getTestOuterQuery($nestedQuery);
        $nestedQuery->where(function ($subq) {
            $this->getTestHasQuery($subq);
        });

        //Query to be tested
        $tenantQuery = $this->getTestOuterQuery($this->model->newQuery());
        $tenantQuery = $this->getTestHasQuery($tenantQuery);

        $this->assertEquals($nestedQuery->getQuery()->getRawBindings(), $tenantQuery->getQuery()->getRawBindings());
        $this->assertEquals(strlen($nestedQuery->toSql()), strlen($tenantQuery->toSql()));
    }

    /** 
     * Test whether builder with multiple global scopes produces
     *  correctly nested queries.
     */
    public function testGlobalScopeNestingQuery()
    {
        $globalScopeModel = new EloquentBuilderTestGlobalScopeStub();
        $globalScopeModel::addGlobalScope(new GlobalScopeStub());
        $this->mockConnectionForModel($globalScopeModel, 'SQLite');

        //Reference query
        $nestedQuery = $this->model
                            ->newQueryWithoutScopes()
                            ->where('tenant_id', '=', 1)
                            ->whereRaw('"table"."deleted_at" is null')
                            ->where(function ($iq2) {
                                $iq2->where('baz', '<>', 1)
                                    ->orWhere('foo', '=', 2);
                            });

        //Query to be tested
        $tenantQuery = $globalScopeModel->newQuery();

        $this->assertEquals($nestedQuery->getQuery()->getRawBindings(), $tenantQuery->getQuery()->getRawBindings());
        $this->assertEquals($nestedQuery->toSql(), $tenantQuery->toSql());
    }

    public function testSoftDeletingMacrosAreSet()
    {
        $globalScopeModel = new EloquentBuilderTestGlobalScopeStub();
        $tenantQuery = $globalScopeModel->newQuery();

        $this->assertEquals($tenantQuery, $tenantQuery->withTrashed());
    }
}

class GlobalScopeStub implements Illuminate\Database\Eloquent\ScopeInterface
{
    public function apply(Illuminate\Database\Eloquent\Builder $builder, Illuminate\Database\Eloquent\Model $model)
    {
        $builder->where('baz', '<>', 1)->orWhere('foo', '=', 2);
    }

    public function remove(Illuminate\Database\Eloquent\Builder $builder, Illuminate\Database\Eloquent\Model $model)
    {
    }
}

/** Stub for a model with multiple global scopes*/
class EloquentBuilderTestGlobalScopeStub extends Illuminate\Database\Eloquent\Model
{
    protected $table = 'table';

    use tenantScopedModelTrait;
    use Illuminate\Database\Eloquent\SoftDeletes;
}
