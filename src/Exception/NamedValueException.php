<?php declare(strict_types=1);
/**
* Contains the Validate\Exception\NamedValueException class.
*
* @author    Craig Manley
* @copyright Copyright © 2018-2024, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
*/
namespace Validate\Exception;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/ValueException.php');


/**
* NamedValueException class.
* Extends ValueException by also including the name of the parameter/key of the value that failed validation.
*
* @package	cmanley
*/
class NamedValueException extends ValueException {
	protected $name;

	/**
	* Constructor.
	*
	* @param string $name the parameter/key name
	* @param string $check name of check that failed
	* @param mixed $value the value that isn't valid
	* @param string $message optional custom message
	*/
	public function __construct(string $name, string $check, $value, ?string $message = null) {
		$this->name  = $name;
		$this->check = $check;
		$this->value = $value;
		if (is_null($message)) {
			$type_of_value = gettype($this->value);
			if (is_object($value)) {
				$type_of_value = get_class($value) . ' ' . $type_of_value;
				# Unfortunately, this method does not have access to the expected value in the case of 'isa' checks.
			}
			else {
				$type_of_value .= ' value';
			}
			$message = 'Parameter "' . $name . '" failed "' . $check . '" validation for ' . $type_of_value;
			if (is_scalar($value)) {
				$message .= ' ' . $this->getStringPlaceholderValue();
			}
		}
		parent::__construct($check, $value, $message);
	}


	/**
	* Return the name of the key, if known, that caused the failure.
	*
	* @return string|null
	*/
	public function getName(): ?string {
		return $this->name;
	}
}
