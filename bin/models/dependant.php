<?php

class dependantModel extends Model
{
	
	public function __construct() {
		
		parent::__construct();
		
		//$this->field('test', 'IntegerField');
		$this->field('title', 'StringField', 200);
		$this->field('numeric', 'IntegerField');
		$this->field('content', 'TextField');
		$this->field('content2', 'TextField');
		$this->field('date', 'DatetimeField');
		
		$this->reference('test');
		$this->reference('test2', 'piedra');
		$this->reference('dependant', 'prueba');
		$this->reference('dependant', 'parent');
	
	}
	
}