<?php declare(strict_types=1);
/**
* Contains the Validation class.
*
* @author    Craig Manley
* @copyright Copyright © 2016-2024, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
*/
namespace Validate;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/Exception/ValueException.php');


use Validate\Exception\ValueException;


/**
* The Validation class encapsulates the constraints used for validating single NON-NULL values.
* This class may be used stand-alone, but it is typically used as the 'validation' value in the Spec constructor.
*/
class Validation {

	# validations:
	protected $allowed_values;    # array of scalar values
	protected $allowed_values_nc; # same as $allowed_values but allowing for case-insensitive string comparisons.
	private   $_allowed_values_nc_lowercased; # lowercased copy of allowed_values_nc
	protected $callbacks; # associative array of key => callback pairs
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

	protected $nocase; # DEPRECATED: use allowed_values_nc instead

	# other:
	protected $last_failure;

	/**
	* Constructor.
	*
	* Below are the supported validations, in the order that they are applied during validation.
	* <pre>
	*	type: allowed type as returned by gettype(), including 'scalar', 'int' (alias of 'integer'), 'float' (alias of 'double')
	*	types: array of allowed types (see type)
	*	resource_type: only used if 'resource' is in 'types' array
	*	max_length: max string length, for scalar types
	*	min_length: min string length, for scalar types
	*	mb_max_length: max multibyte string length, for scalar types
	*	mb_min_length: min multibyte string length, for scalar types
	*	max_value: for numeric types
	*	min_value: for numeric types
	*	isa: allowed object type
	*	regex: validation regex string, e.g. '/^.{1,50}$/s'
	*	callback: boolean closure function that receives the value as argument
	*	callbacks: associative array of boolean closure functions that receive the value as argument
	*	allowed_values: array of scalar values;
	*		if the test value is a scalar, then it must occur in 'allowed_values';
	*		if the test value is an array, then all of it's values must occur in 'allowed_values'
	*	allowed_values_nc: case-insensitive (nc = no case) alternative to allowed_values.
	* </pre>
	*
	* @param array $args associative array of validations
	* @throws \InvalidArgumentException
	*/
	public function __construct(array $args = null) {
		if ($args) {
			foreach ($args as $key => $value) {
				# Process validations:
				if (($key == 'allowed_values') || ($key == 'allowed_values_nc')) {
					if (!(is_array($value) && count($value))) {
						throw new \InvalidArgumentException("The \"$key\" argument must be an array containing at least 1 value.");
					}
					$value = array_unique($value);
					foreach ($value as $s) {
						if (!is_scalar($s)) {
							throw new \InvalidArgumentException("The \"$key\" argument must be an array of scalar values.");
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
					if (!(is_string($value) && strlen($value))) {
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
					if (!(is_string($value) && strlen($value))) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a valid regular expression string.");
					}
					$this->$key = $value;
				}
				elseif ($key == 'resource_type') {
					if (!(is_string($value) && strlen($value))) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a valid resource type.");
					}
					$this->$key = $value;
				}
				elseif ($key == 'type') {
					if (!(is_string($value) && strlen($value))) {
						throw new \InvalidArgumentException("The \"$key\" argument must be a type string.");
					}
					if ($value == 'int') {
						$value = 'integer';
					}
					elseif ($value == 'float') {
						$value = 'double';
					}
					if (is_array($this->types)) { # because 'types' was given
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
					foreach ($value as &$type) {
						if (!is_string($type)) {
							throw new \InvalidArgumentException('The "types" argument must be an array of type strings.');
						}
						/*
						'boolean',
						'integer', # Be careful with integers > 2147483647 (0x7FFFFFFF) or < -2147483648 (0x8000000) as these automatically become floats in PHP.
						'double', # (for historical reasons "double" is returned in case of a float, and not simply "float")
						'string',
						'array',
						'object',
						'resource',
						'NULL',
						'unknown type',
						*/
						# Handle some common type aliases too:
						if ($type == 'int') {
							$type = 'integer';
						}
						elseif ($type == 'float') {
							$type = 'double';
						}
						unset($type);
					}
					if (is_array($this->$key)) { # because 'type' was given
						$this->$key = array_merge($this->$key, $value);
					}
					else {
						$this->$key = $value;
					}
				}
				elseif (substr($key,0,1) === '_') {
					# Silently ignore options prefixed with underscore.
				}
				# Deprecated option(s)
				elseif ($key == 'nocase') {
					trigger_error('Option "nocase" is deprecated; use "allowed_values_nc" instead', E_USER_DEPRECATED);
					$this->$key = (bool) $value;
				}
				else {
					throw new \InvalidArgumentException("Unknown argument \"$key\".");
				}
			}

			# Handle deprecated option "nocase"
			if (!is_null($this->nocase)) {
				if ($this->nocase && $this->allowed_values && !$this->allowed_values_nc) {
					$this->allowed_values_nc = $this->allowed_values;
					$this->allowed_values = null;
				}
			}

			# Force all strings in copy of allowed_values_nc to lowercase.
			if ($this->allowed_values_nc) {
				$tmp = [];
				foreach ($this->allowed_values_nc as $v) {
					if (is_string($v)) {
						$v = \mb_strtolower($v);
						$tmp[$v] = $v;
					}
					else {
						$tmp []= $v;
					}
				}
				$this->_allowed_values_nc_lowercased = $tmp;
			}
		}
	}


	/**
	* PHP magic method that provides public readonly access to protected properties.
	* All options passed into the constructor can be read using property accessors, e.g. print $validation->regex . "\n";
	*
	* @throws \BadMethodCallException
	*/
	#public __get(string $name): mixed {	# PHP8
	public function __get(string $name) {
		# TODO: perhaps replace this reflection code with some simple hash access code. See the comments below why.
		$r = new \ReflectionObject($this);
		$p = null;
		try {
			$p = $r->getProperty($name);
		}
		catch (\ReflectionException $e) {
			# snuff unknown properties with exception message 'Property x does not exist'
		}
		if ($p && ($p->isProtected() || $p->isPublic()) && !$p->isStatic()) {
			$p->setAccessible(true); # Allow access to non-public members.
			return $p->getValue($this); # This design breaks mirrors. Surely the reflection property should know what object was given to ReflectionObject.
		}
		throw new \BadMethodCallException('Attempt to read undefined property ' . get_class($this) . '->' . $name);
	}


	/**
	* Return the name of the check the last validation failed on.
	*
	* @return string|null
	*/
	public function getLastFailure(): ?string {
		return $this->last_failure;
	}


	/**
	* Tests the validity of the given value.
	* If the result is false, then you can call getLastFailure() to get the name of the test that validation failed on.
	*
	* @param mixed $value
	* @return bool
	*/
	public function validate($value): bool {
		if (is_null($value)) {	# the caller (typically Spec) should not call this method with null values
			trigger_error('NULL values are not supported by ' . __METHOD__ . ', returning true', E_USER_WARNING);
			$this->last_failure = null;
			return true;
			#throw new \InvalidArgumentException('NULL values are not supported by ' . __METHOD__);	# in the future
		}
		$tests = 0;	# count tests performed for determining how to handle result for null $value which shouldn't be passed in the first place; see end of method.
		if ($this->types) {
			$k = 'types';
			$type = gettype($value);
			if (!(
				in_array($type, $this->$k)
				||
				(is_scalar($value) && in_array('scalar', $this->$k))
			)) {
				$this->last_failure = $k;
				return false;
			}
			$tests++;
		}
		if ($this->allowed_values) {
			$k = 'allowed_values';
			if (is_scalar($value) || is_null($value)) {
				$strict = is_bool($value) || is_null($value) || ($value === '');
				if (!in_array($value, $this->$k, $strict)) {
					$this->last_failure = $k;
					return false;
				}
			}
			elseif (is_array($value)) {	# do all the given values in $value appear in allowed_values?
				if ($value) {
					if (count($value) != count(array_intersect($value, $this->$k))) {
						$this->last_failure = $k;
						return false;
					}
				}
			}
			else {
				$this->last_failure = $k;
				return false;
			}
			$tests++;
		}
		if ($this->_allowed_values_nc_lowercased) {
			$k = 'allowed_values_nc';
			if (is_string($value)) {
				if (!in_array(mb_strtolower($value), $this->_allowed_values_nc_lowercased)) {
					$this->last_failure = $k;
					return false;
				}
			}
			elseif (is_scalar($value)) {
				$strict = is_null($value) || is_bool($value) || ($value === '');
				if (!in_array($value, $this->$k, $strict)) {
					$this->last_failure = $k;
					return false;
				}
			}
			elseif (is_array($value)) {	# do all the given values in $value appear in allowed_values_nc?
				if ($value) {
					$values_lc = array_map(function($x) { return is_string($x) ? \mb_strtolower($x) : $x; }, $value);
					if (count($value) != count(array_intersect($values_lc, $this->_allowed_values_nc_lowercased))) {
						$this->last_failure = $k;
						return false;
					}
				}
			}
			else {
				$this->last_failure = $k;
				return false;
			}
			$tests++;
		}
		if ($this->resource_type) {
			if (!(is_resource($value) && (get_resource_type($value) == $this->resource_type))) {
				$this->last_failure = 'resource_type';
				return false;
			}
			$tests++;
		}
		if (!is_null($this->max_length)) {
			if (!(is_scalar($value) && (strlen((string)$value) <= $this->max_length))) {
				$this->last_failure = 'max_length';
				return false;
			}
			$tests++;
		}
		if (!is_null($this->min_length)) {
			if (!(is_scalar($value) && (strlen((string)$value) >= $this->min_length))) {
				$this->last_failure = 'min_length';
				return false;
			}
			$tests++;
		}
		if (!is_null($this->mb_max_length)) {
			if (!(is_scalar($value) && (mb_strlen((string)$value) <= $this->mb_max_length))) {
				$this->last_failure = 'mb_max_length';
				return false;
			}
			$tests++;
		}
		if (!is_null($this->mb_min_length)) {
			if (!(is_scalar($value) && (mb_strlen((string)$value) >= $this->mb_min_length))) {
				$this->last_failure = 'mb_min_length';
				return false;
			}
			$tests++;
		}
		if (!is_null($this->max_value)) {
			if (!(is_numeric($value) && ($value <= $this->max_value))) {
				$this->last_failure = 'max_value';
				return false;
			}
			$tests++;
		}
		if (!is_null($this->min_value)) {
			if (!(is_numeric($value) && ($value >= $this->min_value))) {
				$this->last_failure = 'min_value';
				return false;
			}
			$tests++;
		}
		if ($this->isa) {
			if (!(is_object($value) && @is_a($value, $this->isa))) {
				$this->last_failure = 'isa';
				return false;
			}
			$tests++;
		}
		if ($this->regex) {
			if (!(is_scalar($value) && preg_match($this->regex, is_bool($value) ? (string)intval($value) : (string)$value))) {
				$this->last_failure = 'regex';
				return false;
			}
			$tests++;
		}
		if ($this->callback) {
			if (!call_user_func($this->callback, $value)) {
				$this->last_failure = 'callback';
				return false;
			}
			$tests++;
		}
		if ($this->callbacks) {
			foreach ($this->callbacks as $key => $callback) {
				if (!call_user_func($callback, $value)) {
					$this->last_failure = "$key (callback)";
					return false;
				}
			}
			$tests++;
		}

		# Treat null values as invalid if no tests were performed.
		if (!$tests && is_null($value)) {
			$this->last_failure = 'not null';
			return false;
		}

		$this->last_failure = null;
		return true;
	}


	/**
	* Validates the given value and throws a Validate\Exception\ValueException on failure.
	* This method is meant for stand-alone use.
	*
	* @param mixed $value
	* @throws Validate\Exception\ValueException
	*/
	public function validate_ex($value): void {
		if (!is_null($value)) {
			if (!$this->validate($value)) {
				throw new ValueException($this->getLastFailure(), $value);
			}
		}
	}
}
