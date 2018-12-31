<?php
if (isset($argv)) {
	print "Usage:\n";
	print 'phpunit ' . $argv[0] . "\n";
	class PHPUnit_Framework_TestCase {}
}


class Test extends PHPUnit_Framework_TestCase {

	const CLASS_NAME = 'Validate\\Validation';
	const FILE_NAME = '../src/Validation.php';

    public function testRequire() {
    	$file = __DIR__ . '/' . static::FILE_NAME;
		$this->assertFileExists($file);
		$this->assertTrue((boolean) include $file, 'Check include result');
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
			'__get',
			'getLastFailure',
			'validate',
			'validate_ex',
		);
		foreach ($methods as $method) {
			$this->assertTrue(method_exists($class, $method), "Check method $class::$method() exists.");
		}
	}

	public function testCreate() {
		$class = static::CLASS_NAME;
		$o = new $class();
		$this->assertTrue(is_object($o), 'Create empty object.');
		$o = new $class(array(
			# Some of these validations contradict each other, but it's merely a test
			'allowed_values_nc'	=> array('one@two.com', 'two@three.com'),
			'callbacks'			=> array(	 # associative array of key => callback pairs
				'syntax'	=> function($x) { return filter_var($x, FILTER_VALIDATE_EMAIL); },
				'mx'		=> function($x) { return true; },	# dummy check
			),
			'callback'		=> function($x) { return filter_var($x, FILTER_VALIDATE_EMAIL); },
			'isa'			=> 'StdClass',
			'mb_max_length'	=> 50,
			'mb_min_length'	=> 3,
			'max_length'	=> 50,
			'min_length'	=> 3,
			'max_value'		=> 999999,
			'min_value'		=> 0.5,
			'regex'			=> '/^\w+@\w+\.\w+$/',
			'resource_type'	=> 'stream',
			'types'			=> array('string', 'int'),
			'type'			=> 'scalar',
			'_bla'			=> 'Options starting with an underscore should be silently ignored.',
		));
		$this->assertTrue(is_object($o), 'Create object with all supported parameters.');
		$this->assertEquals(50, $o->max_length, 'Read an attribute');
	}

	public function testValidate() {
		$validation = new Validate\Validation(array(
			'callbacks'		=> array(
				'is_lc'	=> function($s) { return mb_strtolower($s) == $s; },
			),
			'mb_max_length'	=> 10,
			'regex'			=> '/a/',
			'type'			=> 'string',
		));
		$tests = array(
			array(
				'input'		=> 'This is too long.',
				'expect'	=> false,
				'expect_last_failure'	=> 'mb_max_length',
			),
			array(
				'input'		=> 'this is also too long.',
				'expect'	=> false,
				'expect_last_failure'	=> 'mb_max_length',
			),
			array(
				'input'		=> 'Yada',
				'expect'	=> false,
				'expect_last_failure'	=> 'is_lc (callback)',
			),
			array(
				'input'		=> 12345,
				'expect'	=> false,
				'expect_last_failure'	=> 'types',
			),
			array(
				'input'		=> 'foofoo',
				'expect'	=> false,
				'expect_last_failure'	=> 'regex',
			),
			array(
				'input'		=> 'bla',
				'expect'	=> true,
				'expect_last_failure'	=> null,
			),
		);
		foreach ($tests as $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_last_failure	= $test['expect_last_failure'];
			$this->assertEquals($expect, $validation->validate($input), "validate(\"$input\") returns expected result.");
			$this->assertEquals($expect_last_failure, $validation->getLastFailure(), "getLastFailure() returns expected result.");
		}
	}

}
