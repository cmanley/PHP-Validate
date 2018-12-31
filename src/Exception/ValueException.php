<?php
/**
* Contains the Validate\Exception\ValueException class.
*
* @author    Craig Manley
* @copyright Copyright © 2018, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
*/
namespace Validate\Exception;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/ValidationException.php');


/**
* ValueException class. Encapsulates the check name, value, and message of the failed validation.
*/
class ValueException extends ValidationException {
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
			$message = 'Failed validation "' . $check . '" for ' . gettype($this->value) . ' value';
			if (is_scalar($value)) {
				$message .= ' ' . $this->getStringPlaceholderValue();
			}
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
	* Return a string representation of the scalar value that caused the failure, for use in error messages.
	* If the value was not a scalar, then null is returned.
	*
	* @return string|null
	*/
	public function getStringPlaceholderValue() {
		if (is_scalar($this->value)) {
			if (is_bool($this->value)) {
				return $this->value ? 'true' : 'false';
			}
			elseif (is_string($this->value)) {
				return '"' . $this->value . '"';
			}
			else {
				return (string)$this->value;
			}
		}
		return null;
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
		if (is_bool($this->value)) {
			return $this->value ? 'true' : 'false';
		}
		elseif (is_scalar($this->value)) {
			return gettype($this->value) . " '" . $this->value . "'";
		}
		return gettype($this->value);
	}
}
