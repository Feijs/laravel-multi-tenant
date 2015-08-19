<?php

use Mockery as m;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use AuraIsHere\LaravelMultiTenant\TenantScope;
use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;

abstract class ComponentTestCase extends PHPUnit_Framework_TestCase
{
	protected $model;
	protected $tenantScope;

	public function setUp()
    {
		$this->tenantScope = new TenantScope;
		$this->tenantScope->addTenant('tenant_id', 1);

		//Mock facades
		App::shouldReceive('make')->atLeast(1)->with("AuraIsHere\LaravelMultiTenant\TenantScope")->andReturn($this->tenantScope);
		Config::shouldReceive('get')->with('laravel-multi-tenant::default_tenant_columns')->andReturn(['tenant_id']);

		$this->model = new ComponentTestCaseModelStub;
		$this->mockConnectionForModel($this->model, 'SQLite');
	}

	public function tearDown()
    {
    	unset($this->model);
    	unset($this->tenantScope);
        m::close();
    }

	protected function mockConnectionForModel($model, $database)
	{
		$grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
		$processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
		$grammar = new $grammarClass;
		$processor = new $processorClass;

		$connection = m::mock('Illuminate\Database\ConnectionInterface', array('getQueryGrammar' => $grammar, 'getPostProcessor' => $processor));
		$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface', array('connection' => $connection));
		
		$class = get_class($model);
		$class::setConnectionResolver($resolver);
	}

	/** 
	 * A query showcasing many clauses 
	 */
	protected function getTestSubQuery($base)
	{
		return $base->where('foo', '=', 2)
					->orWhere('bar', '=', 3)
					->orWhereBetween('baz', [4, 5])
					->orWhereNotNull('quux')
					->whereBazOrBar(6, 7)	//dynamic where
					->orWhere(function($query)
            		{
                		$query->where('wibble', '=', 11)
                      		  ->where('wobble', '<>', 12);
            		})
            		->orWhereExists(function($query)
		            {
		                $query->select('id')
		                      ->from('wobbles')
		                      ->whereRaw('wobbles.wibble_id = wibbles.id');
		            })
		            ->whereHas('selfRelation', function($query) {
		            	$query->whereFlubOrFlob(13,14)
		            		  ->orWhereNull('plugh');
		            });
	}

	/** 
	 * A query showcasing many clauses 
	 */
	protected function getTestOuterQuery($base)
	{
		return $base->join('baztable', 'footable.id', '=', 'baztable.foo_id')
					->leftJoin('quuxtable', 'baz.id', '=', 'quuxtable.baz_id')
					->orderBy('quux', 'desc')
                    ->groupBy('flbr')
                    ->having('fblr', '>', 8)
					->distinct()
					->select('bar')
					->addSelect('foo')
					->skip(9)
					->take(10);
	}

}

/** Stub for a tenant scoped model */
class ComponentTestCaseModelStub extends Illuminate\Database\Eloquent\Model 
{
	protected $table = 'table';
	
	function selfRelation() { return $this->hasMany('ComponentTestCaseModelStub'); }

	use TenantScopedModelTrait;
}
