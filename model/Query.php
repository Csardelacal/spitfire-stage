<?php namespace spitfire\model;

use BadMethodCallException;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroup;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\Query as DatabaseQuery;

/**
 *
 */
class Query
{
	
	use Queriable;
	
	private $db;
	
	private $model;
	
	/**
	 * The with method allows the user to determine relations that should be
	 * proactively resolved.
	 *
	 * @var string[]
	 */
	private $with = [];
	
	/**
	 *
	 * @var DatabaseQuery
	 */
	private $query;
	
	public function __construct(DriverInterface $db, Model $model)
	{
		$this->db = $db;
		$this->model = $model;
		
		$this->query = new DatabaseQuery($this->model->getTable()->getTableReference());
	}
	
	public function getQuery()
	{
		return $this->query;
	}
	
	public function getModel()
	{
		return $this->model;
	}
	
	/**
	 * Pass an array of strings with relationships that should be eagerly
	 * loaded when retrieving data.
	 *
	 * @param string[] $with
	 * @return self
	 */
	public function with(array $with)
	{
		$this->with = $with;
		return $this;
	}
	
	public function first(callable $or = null)
	{
	}
	
	public function all()
	{
		$this->query->selectAll();
		$this->db->query($this->query);
	}
	
	public function range(int $offset, int $size)
	{
	}
}
