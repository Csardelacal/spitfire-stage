<?php namespace spitfire\validation\rules;

use spitfire\validation\ValidationError;

/**
 * This filter ensures that a number provided to it was positive.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class PositiveNumberValidationRule implements \spitfire\validation\ValidationRule
{
	
	/**
	 * A message the validation error generated by this object should carry to give
	 * the end user information about the reason his input was rejected.
	 * 
	 * @var string
	 */
	private $message;
	
	/**
	 * Additional information given to the user in case the validation did not 
	 * succeed. This message can hold additional infos on how to solve the error.
	 * 
	 * @var string
	 */
	private $extendedMessage;
	
	public function __construct($message, $extendedMessage = '') {
		$this->message = $message;
		$this->extendedMessage = $extendedMessage;
	}
	
	/**
	 * Tests a value with this validation rule. Returns the errors detected for
	 * this element or boolean false on no errors.
	 * 
	 * @param mixed $value
	 * @param mixed $source
	 * @return \spitfire\validation\ValidationError|boolean
	 */
	public function test($value) {
		if ($value === null) {
			return false;
		}
		
		if ($value < 0) {
			return new ValidationError($this->message, $this->extendedMessage);
		}
		return false;
	}
	
}