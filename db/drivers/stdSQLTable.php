<?php

namespace spitfire\storage\database\drivers;

use spitfire\storage\database\Table;
use Reference;

abstract class stdSQLTable extends Table
{
	
	/**
	 * Creates the column definitions for each column
	 * 
	 * @return mixed
	 */
	protected function columnDefinitions() {
		$fields = $this->getFields();
		foreach ($fields as $name => $f) {
			$fields[$name] = '`'. $name . '` ' . $f->columnDefinition();
		}
		return $fields;
	}
	
	/**
	 * Creates a list of definitions for CONSTRAINTS defined by the references
	 * this table's model makes to other models.
	 * 
	 * @return array
	 */
	protected function foreignKeyDefinitions() {
		
		$ret = Array();
		$refs = $this->model->getFields();
		
		foreach ($refs as $name => $ref) {
			if (!$ref instanceof Reference) unset($refs[$name]);
		}
		
		if (empty($refs)) return Array();
		
		foreach ($refs as $alias => $ref) {
			//Check the integrity of the remote table
			if ($ref->getTarget() !== $this->model)
				$this->getDb()->table($ref->getTarget())->repair();
			#Get the fields the model references from $ref
			$fields = $ref->getPhysical();
			foreach ($fields as &$field) $field = $field->getName();
			unset($field);
			#Get the table that represents $ref
			$referencedtable = $ref->getTarget()->getTable();
			$primary = $referencedtable->getPrimaryKey();
			foreach ($primary as &$field) $field = $field->getName();
			unset($field);
			//Prepare the statement
			$refstt = sprintf('FOREIGN KEY %s (%s) REFERENCES %s(%s) ON DELETE CASCADE ON UPDATE CASCADE',
				'fk_' . rand(), #Constraint name. Temporary fix, constraints should have proper names
				implode(', ', $fields),
				$referencedtable,
				implode(', ', $primary) 
				);
			
			$ret[] = $refstt;
		}
		
		return $ret;
	}
}
