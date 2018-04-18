<?php

namespace spitfire\storage\database;

abstract class QueryTable
{
	private $table;
	
	/**
	 * The following variables manage the aliasing system inside spitfire. To avoid
	 * having different tables with the same name in them, Spitfire uses aliases
	 * for the tables. These aliases are automatically generated by adding a unique
	 * number to the table's name.
	 * 
	 * The counter is in charge of making sure that every table is uniquely named,
	 * every time a new query table is created the current value is assigned and
	 * incremented.
	 *
	 * @var int
	 */
	private static $counter = 1;
	private $id;
	private $aliased = false;
	
	public function __construct(Table$table) {
		#In case this table is aliased, the unique alias will be generated using this.
		$this->id = self::$counter++;
		
		$this->table = $table;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function newId() {
		$this->id = self::$counter++;
	}
	
	public function setAliased($aliased) {
		$this->aliased = $aliased;
	}
	
	public function isAliased() {
		return $this->aliased;
	}
	
	public function getAlias() {
		/*
		 * Get the name for the table. We use it to provide a consistent naming
		 * system that makes it easier for debugging.
		 */
		$name = $this->table->getLayout()->getTablename();
		
		return $this->aliased? sprintf('%s_%s', $name, $this->id) : $name;
	}
	
	public function getField($name) {
		$of = $this->table->getDb()->getObjectFactory();
		return $of->queryFieldInstance($this, $this->table->getField($name));
	}
	
	public function getFields() {
		$of = $this->table->getDb()->getObjectFactory();
		$fields = $this->table->getFields();
		
		foreach ($fields as &$field) {
			$field = $of->queryFieldInstance($this, $field);
		}
		
		return $fields;
	}
	
	/**
	 * 
	 * @return \spitfire\storage\database\Table
	 */
	public function getTable() {
		return $this->table;
	}
	
	abstract public function definition();
	abstract public function __toString();
}