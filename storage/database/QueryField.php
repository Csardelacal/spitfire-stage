<?php namespace spitfire\storage\database;

use spitfire\model\Field as Logical;

/**
 * The query field object is a component that allows a Query to wrap a field and
 * connect it to itself. This is important for the DBA since it allows the app
 * to establish connections between the different queries when assembling SQL
 * or similar.
 * 
 * When a query is connected to a field, you may use this to establish relationships
 * and create complex queries that can properly be joined.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 * @abstract
 */
abstract class QueryField
{
	/** 
	 * The actual database field. Note that this field is 
	 * 
	 * @var Logical 
	 */
	private $field;
	
	/**
	 *
	 * @var QueryTable
	 */
	private $table;
	
	/** 
	 * To be replaced with the QueryTable object that can be considered "parent"
	 * and provides proper aliasing for this object, therefore removing the need
	 * to maintain a connection with the query.
	 * 
	 * @deprecated since version 0.1-dev 20180403
	 * @var Query 
	 */
	private $query;
	
	public function __construct(QueryTable$table, $field) {
		$this->table = $table;
		$this->field = $field;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20180403
	 * @param type $query
	 */
	public function setQuery($query) {
		$this->query = $query;
	}

	/**
	 * @deprecated since version 0.1-dev 20180403
	 * @return Query
	 */
	public function getQuery() {
		return $this->query;
	}
	
	public function getQueryTable() {
		return $this->table;
	}

	/**
	 * @return Logical
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return bool
	 */
	public function isLogical() {
		return $this->field instanceof Logical;
	}
	
	public function getPhysical() {
		if ($this->isLogical()) {
			$fields = $this->field->getPhysical();
			foreach ($fields as &$field) $field = $this->query->queryFieldInstance($field);
			unset($field);
			return $fields;
		}
	}
	
	abstract public function __toString();
}
