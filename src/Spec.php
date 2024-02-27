<?php declare(strict_types=1);
/**
* Contains the Spec class.
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
require_once(__DIR__ . '/Validation.php');


use Validate\Exception\ValueException;


/**
* A Spec object encapsulates a Validation object as well as some extra attributes.
* See the constructor documentation for all the possible parameters.
* The Spec class is rarely used stand-alone since it is only able to validate a single value.
*
* SYNOPSIS:
*
*	# Typical creation:
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
*	# Lazy creation:
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
*	# Very lazy creation (Validation options instead of a "validation" key):
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
*	# And finally validating something:
*	print (int) $spec->validate("hay") . "\n";
*/
class Spec {

	protected $allow_empty = false;
	protected $default;
	protected $description;
	protected $before;
	protected $after;
	protected $optional = false;
	protected $trim = false;
	protected $validation; # Validation object or null

	# other:
	protected $last_failure;

	/**
	* Constructor.
	*
	* The following options are supported:
	* <pre>
	*	allow_empty	: bool, allow empty strings to be validated and pass 'optional' check.
	*	before		: Callback that takes a reference to the value as argument so that it can mutate it before validation. It may trigger validation failure by returning boolean false.
	*	after		: Callback that takes a reference to the value as argument so that it can mutate it after validation.  It may trigger validation failure by returning boolean false.
	*	default		: Any non-null value; null arguments to validate() are replaced with this
	*	optional	: bool, if true, then null values are allowed
	*	trim		: bool, if true, then whitespace is trimmed off both ends of string values before validation.
	*	description	: Optional description that can be used by user code.
	*	validation	: Validation (constraints) object used to validate non-null values.
	* </pre>
	*
	* If unknown arguments are passed and no "validation" option is given, then the Validation object is created using those arguments.
	*
	* @param array $args associative array of options
	* @throws \InvalidArgumentException
	*/
	public function __construct(array $args = null) {
		if ($args) {
			$boolean_options = array('allow_empty', 'optional', 'trim');
			$unknown_args = [];
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
				# Process boolean options
				elseif (in_array($key, $boolean_options)) {
					$this->$key = (bool) $value;
				}

				elseif (substr($key,0,1) === '_') {
					# Silently ignore options prefixed with underscore.
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
	* PHP magic method that provides public read-only access to protected and public properties.
	* All options passed into the constructor can be read using property accessors, e.g. print $spec->optional . "\n";
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
			return $p->getValue($this);
		}
		throw new \BadMethodCallException('Attempt to read undefined property ' . get_class($this) . '->' . $name);
	}


	/**
	* Returns the value of the 'allow_empty' option as passed into the constructor.
	*
	* @return bool
	*/
	public function allow_empty(): bool {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->allow_empty;
	}


	/**
	* Returns the value of the 'default' option as passed into the constructor.
	*
	* @return mixed
	*/
	public function getDefault() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->default;
	}


	/**
	* Returns the value of the 'description' option as passed into the constructor.
	*
	* @return string|null
	*/
	public function description(): ?string {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->description;
	}


	/**
	* Return the before option as passed in the constructor.
	*
	* @return Callable
	*/
	public function before() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->before;
	}


	/**
	* Return the after option as passed in the constructor.
	*
	* @return Callable
	*/
	public function after() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->after;
	}


	/**
	* Returns the value of the 'optional' option as passed into the constructor.
	*
	* @return bool
	*/
	public function optional() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->optional;
	}


	/**
	* Returns the value of the 'trim' option as passed into the constructor.
	*
	* @return bool
	*/
	public function trim() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->trim;
	}


	/**
	* Return the value of the 'validation' option as passed into or created by the constructor.
	*
	* @return Validation|null
	*/
	public function validation() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated; read the ' . __FUNCTION__ . ' property instead', E_USER_DEPRECATED);
		return $this->validation;
	}


	/**
	* Returns the name of the check that the last validation failed on.
	*
	* @return string|null
	*/
	public function getLastFailure(): ?string {
		return $this->last_failure;
	}


	/**
	* Validates the given argument reference.
	* If 'allow_empty', 'before', or 'after' options were passed into the constructor,
	* then these are applied to the argument in order to modify it in place, which is why it is passed by reference.
	*
	* @param mixed &$arg
	* @return bool
	*/
	public function validate(&$arg): bool {
		if (is_string($arg)) {
			if ($this->trim) {
				$arg = trim($arg);	# trim is multibyte safe
			}
			if (!strlen($arg) && !$this->allow_empty) {
				$arg = null;
			}
		}

		if (is_null($arg) && !is_null($this->default)) {
			#$arg = is_object($this->default) && ($this->default instanceof \Closure) ? call_user_func($this->default) : $this->default;	# old behavior didn't allow default to be an actual closure
			$arg = $this->default;
		}
		else {
			if (!is_null($arg) && $this->before) {
				$x = call_user_func_array($this->before, array(&$arg)); # possible return values are: false, null (void)
				if ($x === false) {
					$this->last_failure = 'callback before';
					return false;
				}
				if (is_string($arg)) {
					if ($this->trim) {
						$arg = trim($arg);	# trim is multibyte safe
					}
					if (!strlen($arg) && !$this->allow_empty) {
						$arg = null;
					}
				}
			}

			if (is_null($arg)) {
				# If optional or if the spec allows the NULL type (which is a bit bizarre), then continue
				if ($this->optional) {
					# null allowed
				}
				#elseif ($this->validation && $this->validation->validate($arg)) {	# if enabling this, then also allow null values in Validation->validate()
				#	# null is one of the allowed 'types' or 'allowed_values' of the Validation, so allow it.
				#}
				else {
					$this->last_failure = 'mandatory';
					return false;
				}
			}
			else {
				if ($this->validation) {
					if (!$this->validation->validate($arg)) {
						$this->last_failure = $this->validation->getLastFailure();
						return false;
					}
				}
				if ($this->after) {
					$x = call_user_func_array($this->after, array(&$arg)); # possible return values are: false, null (void)
					if ($x === false) {
						$this->last_failure = 'callback after';
						return false;
					}
					# Assume callback 'knows' what it's doing in terms of trimming, empty strings, nulling, etc.
				}
			}
		}

		$this->last_failure = null;
		return true;
	}


	/**
	* This simply wraps the validate() method in order to throw a Validate\Exception\ValueException on failure instead of returning a boolean.
	*
	* @param mixed &$arg
	* @throws Validate\Exception\ValueException
	*/
	public function validate_ex(&$arg): void {
		if (!$this->validate($arg)) {
			throw new ValueException($this->getLastFailure(), $arg);
		}
	}
}
