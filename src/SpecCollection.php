<?php declare(strict_types=1);
/**
* Contains the Validate\SpecCollection class.
*
* @author    Craig Manley
* @copyright Copyright © 2016-2024, Craig Manley (www.craigmanley.com)
* @license   http://www.opensource.org/licenses/mit-license.php Licensed under MIT
*/
namespace Validate;


/**
* @ignore Require dependencies.
*/
require_once(__DIR__ . '/Spec.php');



/**
* Encapsulates an (associative) array of Spec objects as an immutable collection.
*/
class SpecCollection implements \Countable, \IteratorAggregate, \ArrayAccess {

	private $pairs; # map of name => Spec pairs
	static private $boolean_spec_true;
	static private $boolean_spec_false;


	/**
	* Constructor.
	* Typically, values must be Spec objects.
	* If boolean values are given, then these are converted into a Spec object with optional => !boolean.
	* If array values are given, then these are passed as arguments into the constructor of a new Spec object.
	*
	* @param array associative array of field name => Spec|bool|array pairs
	* @throws InvalidArgumentException
	*/
	public function __construct(array $pairs = []) {
		foreach ($pairs as $key => &$value) {
			static::_checkKeyValuePair($key, $value);
			unset($value);
		}
		$this->pairs = $pairs;
	}


	/**
	* Returns a Spec object for the given boolean value.
	* This is used internally when assigning boolean values instead of Spec objects.
	*
	* @param bool $value
	* @throws InvalidArgumentException
	*/
	private static function _boolToSpec(bool $value): Spec {
		$property = 'boolean_spec_' . var_export((bool)$value, true);
		if (!static::$$property) {
			static::$$property = (new Spec([
				'optional' => !$value,
			]));
		}
		return static::$$property;
	}


	/**
	* Checks the given key value pair.
	*
	* @param string|int $key
	* @param mixed &$value
	* @throws InvalidArgumentException
	*/
	private static function _checkKeyValuePair($key, &$value): void {
		if (!((is_string($key) && strlen($key)) || is_int($key))) {
			throw new \InvalidArgumentException('Only string or int keys are allowed');
		}
		if (!(is_null($value) || ($value instanceof Spec))) {
			if (is_array($value)) {
				$value = new Spec($value);
			}
			elseif (is_bool($value) || is_int($value)) {
				$value = static::_boolToSpec((bool)$value);
			}
			else {
				throw new \InvalidArgumentException('Array values must be NULL or boolean or array or an instance of Spec. Got ' . gettype($value) . " for $key");
			}
		}
	}


	/**
	* Return count of items in collection.
	* Implements countable
	*
	* @return int
	*/
	public function count(): int {
		return count($this->pairs);
	}


	/**
	* Implements IteratorAggregate
	*
	* @return ArrayIterator
	*/
	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator($this->pairs);	# Uses a copy of pairs; the caller can't mutate this.
	}


	/**
	* Implements ArrayAccess
	*/
	#public function offsetSet(mixed $offset, mixed $value): void {	# PHP8
	public function offsetSet($offset, $value): void {
		throw new \BadMethodCallException("Attempt to set value of key \"$offset\" on immutable instance of " . get_class($this));
		#static::_checkKeyValuePair($offset, $value);
		#$this->pairs[$offset] = $value;
	}


	/**
	* Implements ArrayAccess
	*
	* @return bool
	*/
	#public function offsetExists(mixed $offset): bool {	# PHP8
	public function offsetExists($offset): bool {
		return array_key_exists($offset, $this->pairs);
	}


	/**
	* Implements ArrayAccess
	*/
	#public offsetUnset(mixed $offset): void {	# PHP8
	public function offsetUnset($offset): void {
		throw new \BadMethodCallException("Attempt to unset key \"$offset\" on immutable instance of " . get_class($this));
		#unset($this->pairs[$offset]);
	}


	/**
	* Implements ArrayAccess
	*
	* @return mixed
	*/
	#[\ReturnTypeWillChange]
	#public function offsetGet(mixed $offset): mixed {	# PHP8
	public function offsetGet($offset) {
		return array_key_exists($offset, $this->pairs) ? $this->pairs[$offset] : null;
	}


	/**
	* Returns the collection as an array.
	*
	* @return array
	*/
	public function toArray(): array {
		return $this->pairs;
	}


	/**
	* Returns the keys because array_keys() can't (yet).
	*
	* @return array
	*/
	public function keys(): array {
		return array_keys($this->pairs);
	}

}
