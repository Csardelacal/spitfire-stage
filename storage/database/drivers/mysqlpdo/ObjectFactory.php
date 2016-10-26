<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use spitfire\environment;
use spitfire\exceptions\PrivateException;
use spitfire\model\Field;
use spitfire\storage\database\DB;
use spitfire\storage\database\DBField;
use spitfire\storage\database\drivers\mysqlPDOField;
use spitfire\storage\database\drivers\MysqlPDOQuery;
use spitfire\storage\database\drivers\MysqlPDORestriction;
use spitfire\storage\database\drivers\MysqlPDOTable;
use spitfire\storage\database\ObjectFactoryInterface;
use spitfire\storage\database\Schema;
use spitfire\storage\database\Table;
use TextField;

/*
 * The MIT License
 *
 * Copyright 2016 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The object factory class allows a Database to centralize a point where the 
 * database objects can retrieve certain items from. As opposed to having this
 * algorithms in every class, as some classes would just be overriding one factory
 * method they needed in a completely standard class.
 * 
 * This allows Spitfire to define certain behaviors it expects from DB objects
 * and then have the driver provide this to not disturb Spitfire's logic.
 *
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class ObjectFactory implements ObjectFactoryInterface
{
	
	/**
	 * Creates a new on the fly model. This means that the model is created during
	 * runtime, and by reverse engineering the tables that the database already
	 * has.
	 * 
	 * Please note, that this model would not perfectly replicate a model you could
	 * build with a proper definition yourself.
	 * 
	 * @todo  At the time of writing this, the method does not use adequate types.
	 * @param type $tablename
	 * @return Schema
	 */
	public function getOTFModel($tablename) {
		#Create a Schema we can feed the data into.
		$schema  = new Schema($tablename);
		
		#Make the SQL required to read in the data
		$sql    = sprintf('DESCRIBE `%s%s`', environment::get('db_table_prefix'), $tablename);
		$fields = $this->execute($sql, false);
		
		while ($row = $fields->fetch()) { 
			$schema->{$row['Field']} = new TextField(); 
		}
		
		return $schema;
	}
	
	/**
	 * Creates a new driver specific table. The table is in charge of providing 
	 * the necessary tools for records to be updated, inserted, deleted, etc.
	 * 
	 * @param DB $db
	 * @param string $tablename
	 * @return MysqlPDOTable
	 */
	public function getTableInstance(DB $db, $tablename) {
		return new MysqlPDOTable($db, $tablename);
	}
	
	/**
	 * Creates a new MySQL PDO Field object. This receives the fields 'prototype',
	 * name and reference (in case it references an externa field).
	 * 
	 * This represents an actual field in the DBMS as opposed to the ones in the 
	 * model. That's why here we talk of "physical" fields
	 * 
	 * @todo  This should be moved over to a DBMS specific object factory.
	 * @param Field   $field
	 * @param string  $name
	 * @param DBField $references
	 * @return mysqlPDOField
	 */
	public function getFieldInstance(Field$field, $name, DBField$references = null) {
		return new mysqlPDOField($field, $name, $references);
	}

	public function getQueryInstance($table) {
		return new MysqlPDOQuery($table);
	}

	public function restrictionInstance($query, DBField$field, $value, $operator = null) {
		return new MysqlPDORestriction($query,	$field, $value, $operator);
	}

	public function queryInstance($table) {
		if (!$table instanceof Table) throw new PrivateException('Need a table object');
		
		return new MysqlPDOQuery($table);
	}

	public function makeCollection(Table $table) {
		return new Collection($table);
	}

}
