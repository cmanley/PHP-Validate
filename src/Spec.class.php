<?php
/**
* Contains the Spec class.
*
* Dependencies:
* <pre>
* exceptions.php
* Validation.class.php
* </pre>
*
* @author    Craig Manley
* @copyright Copyright © 2016, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
* @version   $Id: Spec.class.php,v 1.2 2016/02/21 02:08:48 cmanley Exp $
* @package   Validate
*/
namespace Validate;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/exceptions.php');
require_once(__DIR__ . '/Validation.class.php');


/**
* A Spec object encapsulates a Validation object as well as some extra attributes.
* See the constructor documentation for all the possible parameters.
* The Spec class is rarely used stand-alone since it is only able to validate a single value.
*
* SYNOPSIS:
*
*	// Typical creation:
*	$spec = new Validate\Spec(array(
*		'optional'		=> true,
*		'description'	=> 'Just an optional description',
*		'validation'	=> (new Validate\Validation(array(
*			'max_length'	=> 10,
*			'regex'			=> '/a/',
*			'callbacks'		=> array(
*				'is_lc'	=> function($s) { return strtolower($s) == $s; },
*			),
*		))),
*	));
*
*	// Lazy creation:
*	$spec = new Validate\Spec(array(
*		'optional'		=> true,
*		'description'	=> 'Just an optional description',
*		'validation'	=> array(
*			'max_length'	=> 10,
*			'regex'			=> '/a/',
*			'callbacks'		=> array(
*				'is_lc'	=> function($s) { return strtolower($s) == $s; },
*			),
*		),
*	));
*
*	// Very lazy creation (Validation options instead of a "validation" key):
*	$spec = new Validate\Spec(array(
*		'optional'		=> true,
*		'description'	=> 'Just an optional description',
*		'max_length'	=> 10,
*		'regex'			=> '/a/',
*		'callbacks'		=> array(
*			'is_lc'	=> function($s) { return strtolower($s) == $s; },
*		),
*	));
*
*	// And finally validating something:
*	print (int) $spec->validate("hay") . "\n";
*
* @package	cmanley
*/
class Spec {

	protected $allow_empty = false;
	protected $default;
	protected $description;
	protected $before;
	protected $after;
	protected $optional = false;
	protected $validation; // Validation object or null

	// other:
	protected $last_failure;

	/**
	* Constructor.
	*
	* The following options are supported:
	* <pre>
	*	allow_empty	: boolean, allow empty strings to be validated and pass 'optional' check
	*	before		: Callback that takes a reference to the value as argument so that it can mutate it before validation. It may trigger validation failure by returning boolean false.
	*	after		: Callback that takes a reference to the value as argument so that it can mutate it after validation.  It may trigger validation failure by returning boolean false.
	*	default		: Any non-null value (even closures!); null arguments to validate() are replaced with this (or it's result in if it's a closure)
	*	optional	: boolean, if true, then null values are allowed
	*	description	: Optional description that can be used by user code.
	*	validation	: Validation object
	* </pre>
	*
	* If unknown arguments are passed and no "validation" option is given, then the Validation object is created using those arguments.
	*
	* @param array $args associative array of options
	*/
	public function __construct(array $args = null) {
		if ($args) {
			$unknown_args = array();
			foreach ($args as $key => $value) {
				if ($key == 'validation') {
					if (!is_null($value)) {
						if (is_array($value)) {
							$value = new Validation($value);
						}
						elseif (!(is_object($value) && ($value instanceOf Validation))) {
							throw new \InvalidArgumentException("The \"$key\" argument must be null or a Validation object");
						}
						$this->$key = $value;
					}
				}
				elseif (($key == 'after') || ($key == 'before')) {
					if (!is_null($value)) {
						if (!is_callable($value)) {
							throw new \InvalidArgumentException("The \"$key\" argument must be callable, such as a closure or a function name.");
						}
						$this->$key = $value;
					}
				}
				elseif ($key == 'default') {
					$this->$key = $value;
				}
				elseif ($key == 'description') {
					if (!is_null($value)) {
						if (!is_string($value)) {
							throw new \InvalidArgumentException("The \"$key\" argument must be null or a string.");
						}
						$this->$key = $value;
					}
				}
				// Process boolean options
				elseif (in_array($key, array('allow_empty', 'optional'))) {
					$this->$key = (boolean) $value;
				}

				else {
					$unknown_args[$key] = $value;
				}
			}

			if ($unknown_args) {
				if ($this->validation) {
					throw new \InvalidArgumentException('Unknown argument(s): ' . join(', ', array_keys($unknown_args)));
				}
				else {
					$this->validation = new Validation($unknown_args);
				}
			}
		}
	}


	/**
	* PHP magic method that provides public readonly access to protected properties.
	* All options passed into the constructor can be read using property accessors, e.g. print $spec->optional . "\n";
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
			return $p->getValue($this);
		}
		throw new \BadMethodCallException('Attempt to read undefined property ' . get_class($this) . '->' . $key);
	}


	/**
	* Returns the value of the 'allow_empty' option as passed into the constructor.
	*
	* @return boolean
	*/
	public function allow_empty() {
		return $this->allow_empty;
	}


	/**
	* Returns the value of the 'default' option as passed into the constructor.
	*
	* @return string|null
	*/
	public function getDefault() {
		return $this->default;
	}


	/**
	* Returns the value of the 'description' option as passed into the constructor.
	*
	* @return string|null
	*/
	public function description() {
		return $this->description;
	}


	/**
	* Return the before option as passed in the constructor.
	*
	* @return boolean
	*/
	public function before() {
		return $this->before;
	}


	/**
	* Return the after option as passed in the constructor.
	*
	* @return boolean
	*/
	public function after() {
		return $this->after;
	}


	/**
	* Returns the value of the 'optional' option as passed into the constructor.
	*
	* @return boolean
	*/
	public function optional() {
		return $this->optional;
	}


	/**
	* Return the value of the 'validation' option as passed into or created by the constructor.
	*
	* @return Validation|null
	*/
	public function validation() {
		return $this->validation;
	}


	/**
	* Returns the name of the check that the last validation failed on.
	*
	* @return string|null
	*/
	public function getLastFailure() {
		return $this->last_failure;
	}


	/**
	* Validates the given argument reference.
	* If 'before' or 'after' callback options were passed into the constructor,
	* then these are applied to the argument in order to modify it in place, which is why it is passed by reference.
	*
	* @param mixed &$arg
	* @return boolean
	*/
	public function validate(&$arg) {
		if (is_string($arg) && !strlen($arg) && !$this->allow_empty) {
			$arg = null;
		}
		if (is_null($arg) && !is_null($this->default)) {
			$arg = is_object($this->default) && ($this->default instanceof \Closure) ? call_user_func($this->default) : $this->default;
		}
		else {
			if (is_null($arg)) {
				if (!$this->optional) {
					$this->last_failure = 'mandatory';
					return false;
				}
			}
			else {
				if ($this->before) {
					$x = call_user_func_array($this->before, array(&$arg)); // possible return values are: false, null (void)
					if ($x === false) {
						$this->last_failure = 'callback before';
						return false;
					}
				}
				if ($this->validation) {
					if (!$this->validation->validate($arg)) {
						$this->last_failure = $this->validation->getLastFailure();
						return false;
					}
				}
				if ($this->after) {
					$x = call_user_func_array($this->after, array(&$arg)); // possible return values are: false, null (void)
					if ($x === false) {
						$this->last_failure = 'callback after';
						return false;
					}
				}
			}
		}
		$this->last_failure = null;
		return true;
	}


	/**
	* This simply wraps the validate() method in order to throw a ValidationCheckException on failure instead of returning a boolean.
	*
	* @param mixed &$arg
	* @throws ValidationCheckException
	*/
	public function validate_ex(&$arg) {
		if (!$this->validate($arg)) {
			throw new ValidationCheckException($this->getLastFailure(), $arg);
		}
	}
}
