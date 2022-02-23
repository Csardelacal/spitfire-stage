<?php namespace tests\spitfire\model;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\model\relations\BelongsTo;
use spitfire\storage\database\drivers\mysqlpdo\Driver;
use spitfire\storage\database\drivers\mysqlpdo\NoopDriver;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Settings;

class QueryTest extends TestCase
{
	
	private $layout;
	private $layout2;
	
	private $model;
	private $model2;
	
	public function setUp() : void
	{
		$this->layout = new Layout('test');
		$this->layout->putField('_id', 'int:unsigned', false, true);
		$this->layout->putField('my_stick', 'string:255', false, false);
		$this->layout->primary($this->layout->getField('_id'));
		
		$this->layout2 = new Layout('test2');
		$this->layout2->putField('_id', 'int:unsigned', false, true);
		$this->layout2->putField('test_id', 'int:unsigned', false, false);
		$this->layout2->putField('unrelated', 'string:255', false, false);
		$this->layout2->primary($this->layout->getField('_id'));
		$this->layout2->putIndex(new ForeignKey(
			'testforeign',
			$this->layout2->getField('test_id'),
			$this->layout->getTableReference()->getOutput('_id')
		));
		
		
		$this->model = new class ($this->layout) extends Model {
			
		};
		
		$this->model2 = new class ($this->layout2, $this->model) extends Model {
			private $layout;
			private $parent;
			
			public function __construct($layout, $parent)
			{
				$this->layout = $layout;
				$this->parent = $parent;
				parent::__construct($layout);
			}
			
			public function test()
			{
				return new BelongsTo(new Field($this, 'test_id'), new Field($this->parent, '_id'));
			}
		};
	}
	
	public function testBelongsToWhere()
	{
		$query = new Query(
			new NoopDriver(
				Settings::fromArray(['schema' => 'sftest', 'port' => 3306, 'password' => 'root']),
				new Logger('test', [])
			),
			$this->model2
		);
		
		$query->where('test', new class ($this->layout, ['_id' => 1]) extends Model {
			
		});
		$query->all();
	}
	
	public function testBelongsToWhereHas()
	{
		$handler = new TestHandler();
		$query = new Query(
			new NoopDriver(
				Settings::fromArray(['schema' => 'sftest', 'port' => 3306, 'password' => 'root']),
				new Logger('test', [$handler])
			),
			$this->model2
		);
		
		$query->whereHas('test', function (Query $query) {
			$query->where('my_stick', 'is better than bacon');
		});
		
		$query->all();
		
		$this->assertCount(1, $handler->getRecords());
		$this->assertStringContainsString('`_id` FROM `test`', $handler->getRecords()[0]['message']);
		$this->assertStringContainsString("`.`my_stick` = 'is better than bacon' AND", $handler->getRecords()[0]['message']);
	}
}
