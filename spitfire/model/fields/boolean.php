<?php

use spitfire\model\Field;

class BooleanField extends Field
{
	public function getDataType() {
		return Field::TYPE_BOOLEAN;
	}
}