<?php namespace spitfire\storage\database;

use CoffeeBean;
use Model;
use spitfire\exceptions\PrivateException;
use spitfire\storage\database\Schema;

/**
 * This class simulates a table belonging to a database. This way we can query
 * and handle tables with 'compiler-friendly' code that will inform about errors.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class Table
{

	/**
	 * A reference to the database driver loaded. This allows the system to 
	 * use several databases without the models colliding.
	 *
	 * @var DB
	 */
	protected $db;
	
	/**
	 * The model this table uses as template to create itself on the DBMS. This is
	 * one of the key components to Spitfire's ORM as it allows the DB engine to 
	 * create the tables automatically and to discover the data relations.
	 *
	 * @var Schema
	 */
	protected $schema;
	
	/**
	 * Provides access to the table's layout (physical schema) 
	 * 
	 * @var LayoutInterface
	 */
	private $layout;
	
	/**
	 * Provides access to the table's record operations. Basically, a relational
	 * table is composed of schema + relation (data).
	 *
	 * @var Relation
	 */
	private $relation;
	
	/**
	 * Contains the bean this table uses to generate forms for itself. The bean
	 * contains additional data to make the data request more user friendly.
	 * 
	 * @var CoffeeBean
	 */
	protected $bean;
	
	/**
	 * Caches a list of fields that compound this table's primary key. The property
	 * is empty when the table is constructed and collects the primary key's fields
	 * once they are requested for the first time.
	 * 
	 * @var \spitfire\storage\database\Index|null
	 */
	protected $primaryK;
	
	/**
	 * Just like the primary key field, this property caches the field that contains
	 * the autonumeric field. This will usually be the ID that the DB refers to 
	 * when working with the table.
	 *
	 * @var Field
	 */
	protected $autoIncrement;

	/**
	 * Creates a new Database Table instance. The tablename will be used to find
	 * the right model for the table and will be stored prefixed to this object.
	 *
	 * @param DB            $db
	 * @param string|Schema $schema
	 *
	 * @throws PrivateException
	 */
	public function __construct(DB$db, $schema) {
		$this->db = $db;
		$factory = $this->db->getObjectFactory();
		
		if (!$schema instanceof Schema) {
			throw new PrivateException('Table requires a Schema to be passed');
		}
		
		#Attach the schema to this table
		$this->schema = $schema;
		$this->schema->setTable($this);
		
		#Create a database table layout (physical schema)
		$this->layout = $factory->makeLayout($this);
		
		#Create the relation
		$this->relation = $factory->makeRelation($this);
	}
	
	/**
	 * Fetch the fields of the table the database works with. If the programmer
	 * has defined a custom set of fields to work with, this function will
	 * return the overridden fields.
	 * 
	 * @return Field[] The fields this table handles.
	 */
	public function getFields() {
		trigger_error('Deprecated function Table::getFields() called', E_USER_DEPRECATED);
		return $this->layout->getFields();
	}
	
	public function getField($name) {
		trigger_error('Deprecated function Table::getField() called', E_USER_DEPRECATED);
		return $this->layout->getField($name);
	}
	
	/**
	 * Returns the name of the table that is being used. The table name
	 * includes the database's prefix.
	 *
	 * @return string 
	 */
	public function getTablename() {
		trigger_error('Deprecated function Table::getTablename() called', E_USER_DEPRECATED);
		return $this->layout->getTableName();
	}
	
	/**
	 * Returns the database the table belongs to.
	 * @return DB|DB
	 */
	public function getDb() {
		return $this->db;
	}
	
	/**
	 * Get's the table's primary key. This will always return an array
	 * containing the fields the Primary Key contains.
	 * 
	 * @return Field[] Array containing the primary keys in [ 'name' => {DBField object} ] form
	 */
	public function getPrimaryKey() {
		//Check if we already did this
		if ($this->primaryK !== null) { return $this->primaryK; }
		
		//Implicit else
		$fields  = $this->layout->getFields();
		$pk      = Array();
		
		foreach($fields as $name => $field) {
			if ($field->getLogicalField()->isPrimary()) { $pk[$name] = $field; }
		}
		
		return $this->primaryK = (array) $pk;
	}
	
	public function getAutoIncrement() {
		if ($this->autoIncrement) { return $this->autoIncrement; }
		
		//Implicit else
		$fields  = $this->layout->getFields();
		
		foreach($fields as $field) {
			if ($field->getLogicalField()->isAutoIncrement()) { return  $this->autoIncrement = $field; }
		}
		
		 return null;
	}

	/**
	 * Looks for a record based on it's primary data. This can be one of the
	 * following:
	 * <ul>
	 * <li>A single basic data field like a string or a int</li>
	 * <li>A string separated by : to separate those fields (SF POST standard)</li>
	 * <li>An array with the data</li>
	 * </ul>
	 * This function is intended to be used to provide controllers with prebuilt
	 * models so they don't need to fetch it again.
	 *
	 * @todo Move to relation
	 *
	 * @param mixed $id
	 *
	 * @return Model
	 */
	public function getById($id) {
		#If the data is a string separate by colons
		if (!is_array($id)) { $id = explode(':', $id); }
		
		#Create a query
		$table   = $this;
		$primary = $table->getPrimaryKey();
		$query   = $table->getDb()->getObjectFactory()->queryInstance($this);
		
		#Add the restrictions
		while(count($primary))
			{ $query->addRestriction (array_shift($primary), array_shift($id)); }
		
		#Return the result
		$_return = $query->fetch();
		
		return $_return;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20160902
	 * @return Schema
	 */
	public function getModel() {
		return $this->schema;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170801
	 * @return Relation
	 */
	public function getCollection() {
		return $this->db->table($this->schema->getName());
	}
	
	/**
	 * Gives access to the relation, the table's component that manages the data
	 * that the table contains.
	 * 
	 * @return Relation
	 */
	public function getRelation() {
		return $this->relation;
	}
	
	/**
	 * 
	 * @return LayoutInterface
	 */
	public function getLayout(): LayoutInterface {
		return $this->layout;
	}
	
	/**
	 * 
	 * @return Schema
	 */
	public function getSchema() {
		return $this->schema;
	}
	
	/**
	 * Returns the bean this model uses to generate Forms to feed itself with data
	 * the returned value normally is a class that inherits from CoffeeBean.
	 * 
	 * @deprecated since version 0.1-dev 20161220
	 * @return CoffeeBean
	 */
	public function getBean($name = null) {
		
		if (!$name) { $beanName = $this->schema->getName() . 'Bean'; }
		else        { $beanName = $name . 'Bean'; }
		
		$bean = new $beanName($this);
		
		return $bean;
	}

	/**
	 * If the table cannot handle the request it will pass it on to the db
	 * and add itself to the arguments list.
	 *
	 * @param string $name
	 * @param mixed  $arguments
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		
		#We basically reject __call since it is a bad programming habit to rely on 
		#redirecting every call
		trigger_error("Called Table::__call() requesting $name()", E_USER_DEPRECATED);
		
		#Add the table to the arguments for the db
		array_unshift($arguments, $this);
		#Pass on
		return call_user_func_array(Array($this->db, $name), $arguments);
	}

}
