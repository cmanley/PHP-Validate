<?php
/**
* Contains the Validator class.
*
* @author    Craig Manley
* @copyright Copyright © 2016, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
*/
namespace Validate;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/exceptions.php');
require_once(__DIR__ . '/Specs.php');



/**
* Validator objects use their internal Specs to validate and possibly modify (associative) arrays.
* Typical arrays that often require validation are those from form submissions, reading CSV records, and function array-type arguments.
*
* SYNOPSIS
*
*	# Using named parameters:
*	$validator = new Validate\Validator(array(
*		'remove_extra' => true,
*		'specs' => array(
*			'name'	=> array(
*				'type'			=> 'string',
*				'max_length'	=> 2,
*				'max_length'	=> 30,
*			),
*			'birthdate' => array(
*				'type'	=> 'string',
*				'regex'	=> '#^[0-3]\d/[01]\d/\d{4}$#', # expect dd/mm/yyyy
*				'after'	=> function(&$value) { # want yyyy-mm-dd
*					if (is_string($value) && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $matches)) {
*						$value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
*					}
*				},
*				'optional' => true,
*			),
*			'score' => array(
*				'types' => array('float', 'integer'),
*				'max_value'	=> 10,
*				'min_value'	=> 0,
*			),
*		),
*	));
*
*	$params = array(
*		'name'		=> 'Jane',
*		'birthdate'	=> '31/01/1984',
*		'score'		=> 7,
*		'height'	=> 160,
*	),
*	$params = $validator->validate($params);
*
*
*	# Using positional parameters:
*	function my_str_replace() {
*		$args = (new Validate\Validator(array(
*			'specs' => array(
*				array(
*					'type' => 'scalar',
*				),
*				array(
*					'type' => 'scalar',
*				),
*				array(
*	 				'type' => 'scalar',
*				),
*			),
*		)))->validate_pos(func_get_args());
*		$search		= $args[0];
*		$replace	= $args[1];
*		$subject	= $args[2];
*		return str_replace($search, $replace, $subject);
*	}
*	print my_str_replace('him', 'her', $string);
*/
class Validator {

	protected $allow_extra	= false;
	protected $remove_extra	= false;

	protected $trim			= false;
	protected $empty_null	= false;
	protected $keep_null	= false;

	protected $prefix		= '';
	protected $specs		= null;


	/**
	* Constructor.
	*
	* The following options are supported:
	* <pre>
	*	allow_extra  - allow extra parameters for which there are no specs
	*	remove_extra - remove extra parameters for which there are no specs
	*
	*	trim         - trim all string values; applied before the options empty_null and keep_null
	*	empty_null   - replace empty string values with null; applied before the option keep_null
	*	keep_null    - keep key value pairs having null values; by default they are removed
	*
	*	prefix       - for nested validators: set this to whatever you want to prefix key names with in exception messages
	*	specs        - Specs object or array of Spec objects; if not given, then no validation will take place
	* </pre>
	*
	* @param array $args
	*/
	public function __construct(array $args = null) {
		if ($args) {
			$boolean_options = array(
				'allow_extra',
				'remove_extra',
				'empty_null',
				'keep_null',
				'trim',
			);
			foreach ($args as $k => $v) {
				if (is_null($v)) {
					continue;
				}
				if ($k == 'specs') {
					if (is_array($v)) {
						$v = new Specs($v);
					}
					elseif (!(is_object($v) && ($v instanceOf Specs))) {
						throw new \InvalidArgumentException("The '$k' argument must be either a Specs object or an associative array of name => Spec objects");
					}
					$this->$k = $v;
				}
				elseif ($k == 'prefix') {
					if (!is_string($v)) {
						throw new \InvalidArgumentException("The '$k' argument must be a string");
					}
					$this->$k = $v;
				}
				# Process boolean options
				elseif (in_array($k, $boolean_options)) {
					$this->$k = (boolean) $v;
				}
				elseif (substr($key,0,1) === '_') {
					# Silently ignore options prefixed with underscore.
				}
				# Process deprecated options
				elseif ($k == 'empty_delete') {
					$this->keep_null = !$v;
					trigger_error("Option $k is deprecated. Use it's inverse keep_null instead.", E_USER_DEPRECATED);
				}
				else {
					throw new \InvalidArgumentException("Unhandled option '$k'");
				}
			}
		}
	}


	/**
	* PHP magic method that provides public readonly access to protected properties.
	* All options passed into the constructor can be read using property accessors, e.g. print $spec->optional . "\n";
	*/
	public function __get($key) {
		# TODO: perhaps replace this reflection code with some simple hash access code. See the comments below why.
		$r = new \ReflectionObject($this);
		$p = null;
		try {
			$p = $r->getProperty($key);
		}
		catch (\ReflectionException $e) {
			# snuff unknown properties with exception message 'Property x does not exist'
		}
		if ($p && ($p->isProtected() || $p->isPublic()) && !$p->isStatic()) {
			$p->setAccessible(true); # Allow access to non-public members.
			return $p->getValue($this);
		}
		throw new \BadMethodCallException('Attempt to read undefined property ' . get_class($this) . '->' . $key);
	}


	/**
	* Returns the specs passed to the constructor, if any.
	*
	* @return Specs|null
	*/
	public function specs() {
		return $this->specs;
	}


	/**
	* Validates the given associative array and returns the validated result.
	* The result may be mutated depending on the options and specs used.
	*
	* @param array $args associative array
	* @return array
	* @throws ValidationException
	* @throws ValidationNamedCheckException
	*/
	public function validate(array $args) {
		# Make sure keys in $args exist that have default values
		$specs = $this->specs();
		if ($specs) {
			foreach ($specs as $k => $spec) {
				#if (!array_key_exists($k, $args) && !is_null($specs[$k]->getDefault())) {
				if (!array_key_exists($k, $args)) {
					$args[$k] = null;
				}
			}
		}
		# Trim string values if so requested
		if ($this->trim) {
			foreach ($args as $k => &$v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				unset($v);
			}
		}
		# Replace empty string values with null if so requested
		if ($this->empty_null) {
			foreach ($args as $k => &$v) {
				if (is_string($v) && !strlen($v)) {
					$v = null;
				}
				unset($v);
			}
		}
		# Remove key value pairs having null values if so requested
		if (!$this->keep_null) {
			foreach ($args as $k => $v) {
				if (is_null($v) || (is_string($v) && !strlen($v))) {
					if (!($specs && $specs->offsetExists($k) && !is_null($specs[$k]->getDefault()))) { # don't delete args that have default values in their Spec.
						unset($args[$k]);
					}
				}
			}
		}

		# If no spec exists, then return unvalidated arguments.
		if (!$specs) {
			return $args;
		}

		# Validate args
		foreach ($args as $k => &$v) {
			if ($specs->offsetExists($k)) {
				# nop
			}
			elseif ($this->remove_extra) {
				unset($args[$k]);
			}
			elseif (!$this->allow_extra) {
				throw new ValidationException("Unknown key '" . $this->prefix . $k . "'");
			}
			unset($v);
		}
		foreach ($specs as $k => $spec) {
			$v = null;
			if (array_key_exists($k, $args)) {
				$v =& $args[$k];
			}
			if (!$spec->validate($v)) { # also applies defaults or before/after mutators to $v reference
				throw new ValidationNamedCheckException($this->prefix . $k, $spec->getLastFailure(), $v);
			}
			unset($v);
		}
		return $args; # same as input if no befores were applied
	}


	/**
	* Validates a plain positional array of arguments and returns the validated result.
	* The result may be mutated depending on the options and specs used.
	* Since all PHP arrays are really associative, this function reindexes the args and the specs.
	* Because of this, you can use still use strings for keys in either the args or the specs.
	*
	* @param array $args
	* @return array
	* @throws ValidationException
	*/
	public function validate_pos(array $args) {
		$args = array_values($args); # this make sure that args is a sequential numerically indexed array.
		$specs = $this->specs();
		if ($specs) {
			$specs = array_values($this->specs()->toArray()); # make sure that specs is a sequential numerically indexed array.
			$count_args	 = count($args);
			$count_specs = count($specs);

			# Handle too many arguments
			if ($count_args > $count_specs) {
				if (!$this->allow_extra && !$this->remove_extra) {
					throw new ValidationException('Too many arguments given (' . $count_args . ') for the number of specs (' . $count_specs . ')');
					#throw new ValidationException('Unexpected parameter at index ' . $this->prefix . $k);
				}
				if ($this->remove_extra) {
					array_splice($args, $count_specs);
				}
			}
		}

		# Trim string values if so requested
		if ($this->trim) {
			foreach ($args as $k => &$v) {
				if (is_string($v)) {
					$v = trim($v);
				}
				unset($v);
			}
		}
		# Replace empty string values with null if so requested
		if ($this->empty_null) {
			foreach ($args as $k => &$v) {
				if (is_string($v) && !strlen($v)) {
					$v = null;
				}
				unset($v);
			}
		}

		# Validate
		if ($specs) {
			foreach ($specs as $i => $spec) {
				$v = null;
				if ($i < $count_args) {
					$v =& $args[$i];
				}
				if (!$spec->validate($v)) { # also applies before/after mutators to $v reference
					throw new ValidationNamedCheckException($this->prefix . $i, $spec->getLastFailure(), $v);
				}
				unset($v);
			}
		}

		return $args;
	}
}
