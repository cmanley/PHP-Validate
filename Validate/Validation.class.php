<?php
/**
* Contains the Validation class.
*
* Dependencies:
* <pre>
* exceptions.php
* </pre>
*
* @author    Craig Manley
* @copyright Copyright © 2013, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
* @version   $Id: Validation.class.php,v 1.2 2014/06/17 22:42:28 cmanley Exp $
* @package   Validate
*/
namespace Validate;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/exceptions.php');



/**
* Validation class.
* Encapsulates validations and validates.
* This class is designed to also be used stand-alone.
*
* @package	cmanley
*/
class Validation {

	// validations:
	protected $allowed_values;  // array of scalars
	protected $callbacks; // associative array of key => callback pairs
	protected $callback;
	protected $isa;
	protected $mb_max_length;
	protected $mb_min_length;
	protected $max_length;
	protected $min_length;
	protected $max_value;
	protected $min_value;
	protected $regex;
	protected $resource_type;
	protected $types;

	// options:
	protected $nocase;

	// other:
	protected $last_failure;

	/**
	* Constructor.
	*
	* The following validations are supported:
	* <pre>
	*	allowed_values (array)
	*	callback (boolean closure function that receives the value as argument)
	*	callbacks (associative array of boolean closure functions that receive the value as argument)
	*	isa (allowed object type)
	*	max_length (max string length, for scalar types)
	*	min_length (min string length, for scalar types)
	*	mb_max_length (max multibyte string length, for scalar types)
	*	mb_min_length (min multibyte string length, for scalar types)
	*	max_value (for numeric types)
	*	min_value (for numeric types)
	*	regex (validation regex string, e.g. '/^.{1,50}$/s')
	*	resource_type (only used if 'resource' is in 'types' array)
	*	type (allowed type as returned by gettype(), including 'scalar')
	*	types (array of allowed types as returned by gettype(), including 'scalar')
	* </pre>
	*
	* The following options are supported:
	* <pre>
	*	nocase (boolean, make string checks case insensitive)
	* </pre>
	*
	* @param array $args associative array of validations and/or options
	*/
	public function __construct(array $args = null) {
		if ($args) {
			foreach ($args as $key => $value) {
				// Process validations:
				if ($key == 'allowed_values') {
					if (!(is_array($value) && count($value))) {
						throw new \InvalidArgumentException("The \"$key\" argument must be an array containing at least 1 value.");
					}
					foreach ($value as $s) {
						if (!is_scalar($s)) {
							throw new \InvalidArgumentException("The \"$key\" argument must be an array of scalars.");
						}
					}
					$this->$key = $value;
				}
				elseif ($key == 'callback') {
					if (!is_callable($value)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be callable, such as a closure or a function name.");
					}
					$this->$key = $value;
				}
				elseif ($key == 'callbacks') {
					if (!(is_array($value) && count($value))) {
						throw new \InvalidArgumentException("The \"$key\" argument must be an array containing at least 1 value.");
					}
					foreach ($value as $k => $v) {
						if (!is_callable($v)) {
							throw new \InvalidArgumentException("The \"$key\" argument must be an associative array of callables.");
						}
					}
					$this->$key = $value;
				}
				elseif ($key == 'isa') {
					if (!is_string($value) && strlen($value)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a valid class name.");
					}
					$this->$key = $value;
				}
				elseif (in_array($key, array('max_length', 'min_length', 'mb_max_length', 'mb_min_length'))) {
					if (!((is_int($value) || ctype_digit($value))) && ($value >= 0)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be an unsigned integer.");
					}
					$this->$key = $value;
				}
				elseif (($key == 'max_value') || ($key == 'min_value')) {
					if (!is_numeric($value)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be numeric.");
					}
					$this->$key = $value;
				}
				elseif ($key == 'regex') {
					if (!is_string($value) && strlen($value)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a valid regular expression string.");
					}
					$this->$key = $value;
				}
				elseif ($key == 'resource_type') {
					if (!is_string($value) && strlen($value)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a valid resource type.");
					}
					$this->$key = $value;
				}
				elseif ($key == 'type') {
					if (!is_string($value) && strlen($value)) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a type string.");
					}
					if (is_array($this->types)) { // because 'types' was given
						$this->types []= $value;
					}
					else {
						$this->types = array($value);
					}
				}
				elseif ($key == 'types') {
					if (!(is_array($value) && count($value))) {
						throw new \InvalidArgumentException('The "types" argument must be an array containing at least 1 type.');
					}
					foreach ($value as $type) {
						if (!is_string($type)) {
							throw new \InvalidArgumentException('The "types" argument must be an array of type strings.');
						}
						/*
						'boolean',
						'integer', // Be careful with integers > 2147483647 (0x7FFFFFFF) or < -2147483648 (0x8000000) as these automatically become floats in PHP.
						'double', // (for historical reasons "double" is returned in case of a float, and not simply "float")
						'string',
						'array',
						'object',
						'resource',
						'NULL',
						'unknown type',
						*/
					}
					if (is_array($this->$key)) { // because 'type' was given
						$this->$key = array_merge($this->$key, $value);
					}
					else {
						$this->$key = $value;
					}
				}

				// Process boolean options
				elseif (in_array($key, array('nocase'))) {
					$this->$key = (boolean) $value;
				}

				else {
					throw new \InvalidArgumentException("Unknown argument \"$key\".");
				}
			}
		}
	}


	/**
	* PHP magic method that provides public readonly access to protected properties.
	* All options passed into the constructor can be read using property accessors, e.g. print $validation->regex . "\n";
	*/
	public function __get($key) {
		// TODO: perhaps replace this reflection code with some simple hash access code. See the comments below why.
		$r = new \ReflectionObject($this);
		$p = null;
		try {
			$p = $r->getProperty($key);
		}
		catch (\ReflectionException $e) {
			// snuff unknown properties with exception message 'Property x does not exist'
		}
		if ($p && ($p->isProtected() || $p->isPublic()) && !$p->isStatic()) {
			$p->setAccessible(true); // Allow access to non-public members.
			return $p->getValue($this); // This design breaks mirrors. Surely the reflection property should know what object was given to ReflectionObject.
		}
		throw new \BadMethodCallException('Attempt to read undefined property ' . get_class($this) . '->' . $key);
	}


	/**
	* Return the name of the check the last validation failed on.
	*
	* @return string
	*/
	public function getLastFailure() {
		return $this->last_failure;
	}


	/**
	* Validates the given argument if it is non-null.
	*
	* @param mixed $arg
	* @return boolean
	*/
	public function validate($arg) {
		if (!is_null($arg)) {
			if ($this->allowed_values) {
				if (!(
					is_scalar($arg)
					&&
					($this->nocase ? in_array(mb_strtolower($arg), array_map('mb_strtolower', $this->allowed_values)) : in_array($arg, $this->allowed_values))
				)) {
					$this->last_failure = 'allowed_values';
					return false;
				}
			}
			if ($this->callback) {
				if (!call_user_func($this->callback, $arg)) {
					$this->last_failure = 'callback';
					return false;
				}
			}
			if ($this->callbacks) {
				foreach ($this->callbacks as $key => $callback) {
					if (!call_user_func($callback, $arg)) {
						$this->last_failure = "callback $key";
						return false;
					}
				}
			}
			if ($this->isa) {
				if (!(is_object($arg) && @is_a($arg, $this->isa))) {
					$this->last_failure = 'isa';
					return false;
				}
			}
			if (!is_null($this->max_length)) {
				if (!(is_scalar($arg) && (strlen($arg) <= $this->max_length))) {
					$this->last_failure = 'max_length';
					return false;
				}
			}
			if (!is_null($this->min_length)) {
				if (!(is_scalar($arg) && (strlen($arg) >= $this->min_length))) {
					$this->last_failure = 'min_length';
					return false;
				}
			}
			if (!is_null($this->mb_max_length)) {
				if (!(is_scalar($arg) && (mb_strlen($arg) <= $this->mb_max_length))) {
					$this->last_failure = 'mb_max_length';
					return false;
				}
			}
			if (!is_null($this->mb_min_length)) {
				if (!(is_scalar($arg) && (mb_strlen($arg) >= $this->mb_min_length))) {
					$this->last_failure = 'mb_min_length';
					return false;
				}
			}
			if (!is_null($this->max_value)) {
				if (!(is_numeric($arg) && ($arg <= $this->max_value))) {
					$this->last_failure = 'max_value';
					return false;
				}
			}
			if (!is_null($this->min_value)) {
				if (!(is_numeric($arg) && ($arg >= $this->min_value))) {
					$this->last_failure = 'min_value';
					return false;
				}
			}
			if ($this->regex) {
				if (!(is_scalar($arg) && preg_match($this->regex, $arg))) {
					$this->last_failure = 'regex';
					return false;
				}
			}
			if ($this->resource_type) {
				if (!(is_resource($arg) && (get_resource_type($arg) == $this->resource_type))) {
					$this->last_failure = 'resource_type';
					return false;
				}
			}
			if ($this->types) {
				$type = gettype($arg);
				if (!(
					in_array($type, $this->types)
					||
					(is_scalar($arg) && in_array('scalar', $this->types))
					//||
					//(is_float($arg) && in_array('float', $this->types)) // just in case 'float' was defined instead of 'double'.
				)) {
					$this->last_failure = 'types';
					return false;
				}
			}
		}
		$this->last_failure = null;
		return true;
	}


	/**
	* Validates the given argument and throws a ValidationCheckException on failure.
	* This method is meant for stand-alone use.
	*
	* @param mixed $arg
	* @throws ValidationCheckException
	*/
	public function validate_ex($arg) {
		if (!is_null($arg)) {
			if (!$this->validate($arg)) {
				throw new ValidationCheckException($this->getLastFailure(), $arg);
			}
		}
	}
}
