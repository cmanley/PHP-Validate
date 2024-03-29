<?php declare(strict_types=1);
namespace PHPUnit\Framework;
if (isset($argv)) {
	print "Usage:\n";
	print 'phpunit ' . $argv[0] . "\n";
	class TestCase {}
}
error_reporting(error_reporting() | E_DEPRECATED);

class T extends TestCase {

	const CLASS_NAME = 'Validate\\SpecCollection';
	const FILE_NAME = '../src/SpecCollection.php';

    public function testRequire() {
    	$file = __DIR__ . '/' . static::FILE_NAME;
		$this->assertFileExists($file);
		$this->assertTrue((bool) include $file, 'Check include result');
    }

    public function testClassExists() {
    	$class = static::CLASS_NAME;
		$this->assertTrue(class_exists($class), 'Check that class name "' . $class . '" exists.');
	}

    public function testMethodsExist() {
		$class = static::CLASS_NAME;
		$methods = array(
			# public
			'__construct',
			'toArray',
			'keys',

			# for Countable interface
			'count',

			# for IteratorAggregate interface
			'getIterator',

			# for ArrayAccess interface
			'offsetSet',
			'offsetExists',
			'offsetUnset',
			'offsetGet',
		);
		foreach ($methods as $method) {
			$this->assertTrue(method_exists($class, $method), "Check method $class::$method() exists.");
		}
	}

	public function testInterfaces() {
		$class = static::CLASS_NAME;
		$expect = array('ArrayAccess', 'Countable', 'IteratorAggregate', 'Traversable'); # Traversable is part of IteratorAggregate
		$got = class_implements($class, false);
		sort($got);
		$this->assertEquals($expect, $got, "$class implements the interfaces " . join(', ', $expect));
	}

	public function testCreate() {
		$class = static::CLASS_NAME;
		$o = new $class();
		$this->assertTrue(is_object($o), 'Create empty object.');
		$o = new $class(array(
			'firstname'	=> array(
				'description'	=> 'First name',
				'mb_max_length'	=> 10,
				'regex'			=> '/^[A-Z][a-z]+$/',
				'type'			=> 'string',
			),
			'surname'	=> 1,	# shortcut for Spec with 'optional' => !value
			'age'		=> array(
				'optional'		=> true,
				'mb_max_length'	=> 3,
				'regex'			=> '/^\d{1,3}$/',
				'types'			=> array('int', 'string'),
				'_bla'			=> 'Options starting with an underscore should be silently ignored.',
			),
		));
		$this->assertTrue(is_object($o), 'Create object with multiple Spec objects.');
		$this->assertEquals(3, $o->count(), 'Count Spec objects');
	}

	public function testValidate() {
		$tests = array(
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
					'age'		=> 18,
				),
				'expect'	=> true,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
				),
				'expect'	=> true,
			),
			array(
				'input'		=> array(
					'surname'	=> 'Doe',
					'age'		=> 18,
				),
				'expect'	=> false,
			),
			array(
				'input'		=> [],
				'expect'	=> false,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'too_lazy',
					'surname'	=> 'Doe',
				),
				'expect'	=> false,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
					'age'		=> '',
				),
				'expect'	=> true,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
					'age'		=> null,
				),
				'expect'	=> true,
			),
		);
		$specs = new \Validate\SpecCollection(array(
			'firstname'	=> array(
				'description'	=> 'First name',
				'mb_max_length'	=> 10,
				'regex'			=> '/^[A-Z][a-z]+$/',
				'type'			=> 'string',
			),
			'surname'	=> 1,	# shortcut for Spec with 'optional' => !value
			'age'		=> array(
				'optional'		=> true,
				'mb_max_length'	=> 3,
				'regex'			=> '/^\d{1,3}$/',
				'types'			=> array('int', 'string'),
			),
		));
		foreach ($tests as $name => $test) {
			$input = $test['input'];
			$valid = true;
			foreach ($specs as $k => $spec) {
				$v = @$input[$k];
				$valid = $valid && $spec->validate($v);
			}
			$input	= $test['input'];
			$expect	= $test['expect'];
			$this->assertEquals($expect, $valid, "Validation of all input of test $name had expected result.");
		}
	}

}





if (isset($argv)) {
	require_once(__DIR__ . '/' . T::FILE_NAME);
	if (1) {
		$tests = array(
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
					'age'		=> 18,
				),
				'expect'	=> true,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
				),
				'expect'	=> true,
			),
			array(
				'input'		=> array(
					'surname'	=> 'Doe',
					'age'		=> 18,
				),
				'expect'	=> false,
			),
			array(
				'input'		=> [],
				'expect'	=> false,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'too_lazy',
					'surname'	=> 'Doe',
				),
				'expect'	=> false,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
					'age'		=> '',
				),
				'expect'	=> true,
			),
			array(
				'input'		=> array(
					'firstname'	=> 'Jane',
					'surname'	=> 'Doe',
					'age'		=> null,
				),
				'expect'	=> true,
			),
		);
		$specs = new \Validate\SpecCollection(array(
			'firstname'	=> array(
				'description'	=> 'First name',
				'mb_max_length'	=> 10,
				'regex'			=> '/^[A-Z][a-z]+$/',
				'type'			=> 'string',
			),
			'surname'	=> 1,	# shortcut for Spec with 'optional' => !value
			'age'		=> array(
				'optional'		=> true,
				'mb_max_length'	=> 3,
				'regex'			=> '/^\d{1,3}$/',
				'types'			=> array('int', 'string'),
			),
		));
		foreach ($tests as $name => $test) {
			print "Test $name ";
			$input = $test['input'];
			$valid = true;
			foreach ($specs as $k => $spec) {
				$v = @$input[$k];
				$my_valid = $spec->validate($v);
				print "	validate $k value=" . json_encode($v) . '	valid=' . json_encode($my_valid);
				if (!$my_valid) {
					print '	getLastFailure()=' . $spec->getLastFailure();
				}
				print "\n";
				$valid = $valid && $my_valid;
			}
			$input	= $test['input'];
			$expect	= $test['expect'];
			if ($valid === $expect) {
				print '	OK';
			}
			else {
				print '	FAIL: ' . json_encode($expect)  . ' != ' . json_encode($valid);
			}
			print "\n";
		}
	}
}
