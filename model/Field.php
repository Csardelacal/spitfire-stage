<?php namespace spitfire\model;

use spitfire\Model;
use spitfire\model\Schema;
use spitfire\storage\database\Table;
use spitfire\validation\ValidationError;

/**
 * Represents a table's field in a database. Contains information about the
 * table the field belongs to, the name of the field and if it is (or not) a
 * primary key or auto-increment field.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
abstract class Field
{
	/**
	 * This constants represent the list of basic data types that spitfire 
	 * supports. This should be supported by the DBMS to be used.
	 * 
	 * @todo Move or remove. The idea of these constants was to define the field's
	 * raw data in a way that DBMS's could understand. Since we now do support
	 * instanceof tests, these are just a superfluous way of restating what the 
	 * system already knows about the field. And in the event of this data 
	 * conflicting with the data provided by the class even causing trouble.
	 */
	const TYPE_INTEGER   = 'int';
	const TYPE_LONG      = 'long';
	const TYPE_FLOAT     = 'float';
	const TYPE_BOOLEAN   = 'boolean';
	const TYPE_STRING    = 'string';
	const TYPE_FILE      = 'file';
	const TYPE_TEXT      = 'txt';
	const TYPE_DATETIME  = 'datetime';
	const TYPE_REFERENCE = 'reference';
	const TYPE_CHILDREN  = 'children';
	const TYPE_BRIDGED   = 'bridged';
	
	/**
	 * Contains a reference to the parent model. This allows this field to 
	 * interact with it and to retrieve information about the model, the 
	 * table it is in and even the DB engine that holds them.
	 * 
	 * @var Schema
	 * @todo Rename to schema
	 */
	private $schema;
	
	/**
	 * Contains the name of the field inside of the model. This name should 
	 * be unique and is recommended not to conatin any underscores or hyphens
	 * other than those generated by Spitfire itself to indicate it's relation
	 * with other fields and models.
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * This indicates whether the content of this field needs to be enforced
	 * to be unique. This restriction is handled by the DBMS and Spitfire
	 * does not guarantee that this is respected.
	 *
	 * @var boolean 
	 * @deprecated since version 0.1-dev 20170804
	 */
	private $unique = false;
	
	/**
	 * Defines whether the field's value can be set to null or not. In case this 
	 * happens the database will allow null values for this field.
	 *
	 * @var boolean
	 */
	private $nullable = true;
	
	/**
	 * This property indicates whether the field belongs or not to the model
	 * 's primary Key. This key is the one used to identify records uniquely.
	 *
	 * @var boolean 
	 * @deprecated since version 0.1-dev 20170804
	 */
	private $primary = false;
	
	/**
	 * Indicates whether the field is to be incremented everytime a new 
	 * record is inserted into the DBMS. This task is handled by the DBMS and
	 * Spitfire therefore does not guarantee nor enforce that the task of
	 * incrementing the field is handled correctly.
	 *
	 * @var boolean 
	 */
	private $auto_increment = false;
	
	/**
	 * Contains the default value for this field. In the event of this field 
	 * being null but the field not-nullable, the driver should define a proper
	 * "empty" value for it's data type.
	 *
	 * @var mixed
	 */
	private $default;
	
	/**
	 * Contains the physical (DB) fields that are used to store the data 
	 * this Field generates. For most data types Spitfire will just generate
	 * one physical field for each logical one.
	 * 
	 * Only common exception to this are the Referenced fields, which need to
	 * generate one field for each remote primary field.
	 *
	 * @var \spitfire\storage\database\Field|\spitfire\storage\database\Field[]
	 */
	private $physical;
	
	/**
	 * Sets the physical equivalent of this field. This needs to be a instance
	 * of DBField or an array of those. 
	 * 
	 * [NOTICE] As it is uncommon for this function to be called with invalid
	 * data (due to be being mainly an internal function) there is no validation
	 * being done for the type of data that is inserted. Be careful!
	 * 
	 * @param \spitfire\storage\database\Field|\spitfire\storage\database\Field[] $physical
	 */
	public function setPhysical($physical)
	{
		$this->physical = $physical;
	}
	
	/**
	 * This method initializes the Physical equivalent(s) of the Field,
	 * this is done in a separate method from the contructor to ease 
	 * overriding of this logic.
	 */
	public function makePhysical()
	{
		#The instancing is redirected to the table
		$table = $this->getModel()->getTable();
		return $table->getDb()->getObjectFactory()->getFieldInstance($this, $this->getName());
	}
	
	/**
	 * This method returns an array of Fields. Even if the field has only one db
	 * equivalent the return value will be an array, for consistency with fields
	 * which return several ones.
	 * 
	 * @return \spitfire\storage\database\Field[]
	 */
	public function getPhysical()
	{
		if ($this->physical === null) {
			$this->physical = $this->makePhysical(); 
		}
		
		if (is_array($this->physical)) {
			return $this->physical; 
		}
		else {
			return array($this->physical); 
		}
	}
	
	/**
	 * Returns whether the field is autoincrement. This means, everytime a 
	 * new record of this kind is created the auto_increment field is 
	 * increased by 1.
	 * 
	 * @return boolean
	 */
	public function isAutoIncrement()
	{
		return $this->auto_increment;
	}

	/**
	 * Defines whether the field is used as autoincrement field. This setting
	 * will have no effect on any field that is not an integer field.
	 *
	 * @param boolean $ai
	 *
	 * @return self
	 */
	public function setAutoIncrement($ai)
	{
		$this->auto_increment = !!$ai;
		return $this;
	}
	
	/**
	 * Returns the name of this field. The name identifies this field among
	 * the rest of the fields of the same model / table.
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Sets the name of this field. The name identifies this field among
	 * the rest of the fields of the same model / table.
	 * 
	 * @param string $name New name of the field.
	 * @return \spitfire\model\Field The field, for method chaining
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	
	#@TODO: Document
	public function setNullable($nullable)
	{
		$this->nullable = $nullable;
	}
	
	public function getNullable()
	{
		return $this->nullable;
	}
	
	public function getDefault()
	{
		return $this->default;
	}
	
	public function setDefault($default)
	{
		$this->default = $default;
		return $this;
	}
	
	public function getSchema()
	{
		return $this->schema;
	}

	
	/**
	 * Gets the parent model for this field. This allows the field to 
	 * deliver data about which fields it's a sibling to and which fields
	 * it refers too.
	 * 
	 * @return Schema
	 */
	public function getModel()
	{
		return $this->schema;
	}
	
	/**
	 * Defines which model this field belongs to. This data basically is 
	 * redundant but quickens development and makes it more efficient to
	 * find the model for the field.
	 * 
	 * @deprecated since version 0.1-dev 201710131759
	 * @param Schema $model
	 */
	public function setModel($model)
	{
		trigger_error('Field::setModel has been deprecated', E_USER_DEPRECATED);
		return $this->setSchema($model);
	}
	
	/**
	 * Defines which model this field belongs to. This data basically is 
	 * redundant but quickens development and makes it more efficient to
	 * find the model for the field.
	 * 
	 * @param Schema $schema
	 */
	public function setSchema(Schema$schema)
	{
		$this->schema = $schema;
	}
	
	/**
	 * Returns the table this field belongs to. This is just a shortcut method 
	 * provided to allow making logical fields and DBFields compatible.
	 * 
	 * @return Table
	 */
	public function getTable()
	{
		return $this->schema->getTable();
	}
	
	/**
	 * Returns true if the field belongs to the table's primary key. Keep in
	 * mind that a primary key can cover several fields.
	 * 
	 * @deprecated since version 0.1-dev 20170804
	 * @return boolean
	 */
	public function isPrimary()
	{
		return $this->primary || $this->auto_increment;
	}
	
	/**
	 * Defines whether this belongs to the primary key or not. This will 
	 * also automatically set the unique flag to true.
	 * 
	 * @deprecated since version 0.1-dev 20170804
	 * @param boolean $primary
	 * @return self
	 */
	public function setPrimary($primary)
	{
		$this->primary = !!$primary;
		return $this;
	}
	
	/**
	 * Returns true if the field has a unique flag, alas it's content cannot 
	 * be repeated among several records.
	 * 
	 * [NOTICE] If the field is a primary key this will return true. Due to
	 * the primary having to be unique.
	 * 
	 * @deprecated since version 0.1-dev 20170804
	 * @return boolean
	 */
	public function isUnique()
	{
		//TODO: BUG, requires revision for multi field primaries if ($this->primary) return true;
		return $this->unique;
	}
	
	/**
	 * Allows you to define whether the current field only allows unique 
	 * (non repeated) values. When using data that only should allow 
	 * unique values setting this flag to true will consistently increase
	 * the speed of database queries.
	 * 
	 * @deprecated since version 0.1-dev 20170804
	 * @param boolean $unique
	 * @return self
	 */
	public function setUnique($unique)
	{
		$this->unique = $unique;
		return $this;
	}
	
	/**
	 * The return value of this method indicates whether the DBMS SHOULD 
	 * create an index on this field. This depends on the kind of content
	 * and the settings of this field.
	 * 
	 * Spitfire automatically chooses the right settings for you, this 
	 * function should be overriden by methods for field types that have 
	 * higher performance with specific values.
	 * 
	 * @deprecated since version 0.1-dev 20170804
	 * @return boolean
	 */
	public function isIndexed()
	{
		return ($this->primary || $this->unique);
	}
	
	/**
	 * Returns the field the bean should use to render this. In case it returns null
	 * it means the field has no suggestions and the Bean should choose the way 
	 * to render it
	 * 
	 * @param \CoffeeBean $bean
	 * @param Field       $field
	 * @param string      $caption
	 * @return Field|null
	 */
	public function getBeanField($bean, $field, $caption)
	{
		return null;
	}
	
	/**
	 * 
	 * @todo Validation should be either attributed to the model or the beans
	 * @deprecated since version 0.1-dev 20171016
	 * @param type $value
	 * @return boolean|ValidationError
	 */
	public function validate($value)
	{
		if (!$this->nullable && !$value) {
			return new ValidationError(_t('err_field_null'));
		}
		return false;
	}
	
	/**
	 * Returns the adapter this field wants to use for the database interaction,
	 * usually this will be a BaseAdapter that allows the most basic interaction
	 * with the most basic kinds of fields.
	 * 
	 * But you can also create custom adapters that manage complex data and return
	 * them here.
	 * 
	 * @param Model $model The model to contain the adapter
	 * @return adapters\AdapterInterface The new adapter
	 */
	abstract public function getAdapter(Model$model);

	/**
	 * This method informs the system what primary datatype the overriding 
	 * Field class uses. You can define custom classes that store data into
	 * any DBMS by defining which primary data they use.
	 * 
	 * @deprecated since version 0.1-dev 20170804
	 */
	abstract public function getDataType();
	
	abstract public function getConnectorQueries(\spitfire\storage\database\Query$parent);
	
	/**
	 * Returns the name of the Field, this is purely a conveiencce method
	 * to shorten code writing and make it easier to read.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}
}
