<?php
/**
* Contains the ValidationException based classes.
*
* @author    Craig Manley
* @copyright Copyright © 2013, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
* @version   $Id: exceptions.php,v 1.1 2013/12/10 23:27:57 cmanley Exp $
* @package   Validate
*/
namespace Validate;


/**
* ValidationException class
*
* @package	cmanley
*/
class ValidationException extends \Exception {}




/**
* ValidationCheckException class.
*
* @package	cmanley
*/
class ValidationCheckException extends ValidationException {
	protected $check;
	protected $value;

	/**
	* Constructor.
	*
	* @param string $check name of check that failed
	* @param mixed $value the value that isn't valid
	* @param string $message optional custom message
	* @param array $options
	*/
	public function __construct($check, $value, $message = null) {
		$this->check = $check;
		$this->value = $value;
		if (is_null($message)) {
			$message = "The validation check '$check' failed for value " . $this->getValueSimple();
		}
		parent::__construct($message);
	}

	/**
	* Return the name of the check that failed.
	*
	* @return string
	*/
	public function getCheck() {
		return $this->check;
	}

	/**
	* Return the value that caused the failure.
	*
	* @return mixed
	*/
	public function getValue() {
		return $this->value;
	}

	/**
	* Return a simplified string representation of the value that caused the failure.
	*
	* @return string
	*/
	public function getValueSimple() {
		if (is_scalar($this->value)) {
			return $this->value;
		}
		return gettype($this->value);
	}
}





/**
* ValidationNamedCheckException class.
*
* @package	cmanley
*/
class ValidationNamedCheckException extends ValidationCheckException {
	protected $name;

	/**
	* Constructor.
	*
	* @param string $name the parameter/key name
	* @param string $check name of check that failed
	* @param mixed $value the value that isn't valid
	* @param string $message optional custom message
	* @param array $options
	*/
	public function __construct($name, $check, $value, $message = null) {
		$this->name  = $name;
		$this->check = $check;
		$this->value = $value;
		if (is_null($message)) {
			$message = "Parameter '$name' failed the validation check '$check' for value " . $this->getValueSimple();
		}
		parent::__construct($check, $value, $message);
	}


	/**
	* Return the name of the key, if known, that caused the failure.
	*
	* @return string|null
	*/
	public function getName() {
		return $this->name;
	}
}
