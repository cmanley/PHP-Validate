<?php
if (isset($argv)) {
	print "Usage:\n";
	print 'phpunit ' . $argv[0] . "\n";
	class PHPUnit_Framework_TestCase {}
}


class Test extends PHPUnit_Framework_TestCase {

	const CLASS_NAME = 'Validate\\Validator';
	const FILE_NAME = '../src/Validator.php';

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
			'specs',
			'validate',
			'validate_pos',
		);
		foreach ($methods as $method) {
			$this->assertTrue(method_exists($class, $method), "Check method $class::$method() exists.");
		}
	}

	public function testCreate() {
		$class = static::CLASS_NAME;
		$o = new $class();
		$this->assertTrue(is_object($o), 'Create empty object.');

		$specs = array(
			'name'	=> array(
				'type'			=> 'string',
				'max_length'	=> 2,
				'max_length'	=> 30,
			),
			'birthdate' => array(
				'type'	=> 'string',
				'regex'	=> '/^\d{4}-[01]\d-[0-3]\d$/', # should be yyyy-mm-dd
				'before'	=> function(&$value) { # expect dd/mm/yyyy
					if (is_string($value) && preg_match('#^([0-3]\d)/([01]\d)/(\d{4})$#', $value, $matches)) {
						$value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
					}
				},
				'optional' => true,
			),
			'score' => array(
				'types' => array('float', 'integer'),
				'max_value'	=> 10,
				'min_value'	=> 0,
				'_bla'		=> 'Options starting with an underscore should be silently ignored.',
			),
		);
		$o = new $class(array(
			'allow_extra'	=> false,
			'empty_delete'	=> false,
			'empty_null'	=> true,
			#'prefix'
			'remove_extra'	=> true,
			'specs'			=> $specs,
		));
		$this->assertTrue(is_object($o), 'Create object with all supported parameters.');
		$this->assertEquals(true, $o->remove_extra, 'Read an attribute');
	}

	public function testValidate() {
		$specs = array(
			'name'	=> array(
				'type'			=> 'string',
				'max_length'	=> 2,
				'max_length'	=> 30,
				'after'	=> function(&$value) {
					if (is_string($value)) {
						$value = strtoupper($value);
					}
				},
			),
			'birthdate' => array(
				'type'	=> 'string',
				'regex'	=> '/^\d{4}-[01]\d-[0-3]\d$/', # should be yyyy-mm-dd
				'before'	=> function(&$value) { # expect dd/mm/yyyy
					if (is_string($value) && preg_match('#^([0-3]\d)/([01]\d)/(\d{4})$#', $value, $matches)) {
						$value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
					}
				},
				'optional' => true,
			),
			'score' => array(
				'types' => array('float', 'integer'),
				'max_value'	=> 10,
				'min_value'	=> 0,
			),
		);
		$validator = new Validate\Validator(array(
			'allow_extra'	=> false,
			'empty_delete'	=> false,
			'empty_null'	=> true,
			#'prefix'
			'remove_extra'	=> true,
			'specs'			=> $specs,
		));
		$tests = array(
			array(
				'input'		=> array(
					'name'		=> 'Jane',
					'birthdate'	=> '31/01/1984',
					'score'		=> 7,
				),
				'expect'	=> array(
					'name'		=> 'JANE',
					'birthdate'	=> '1984-01-31',
					'score'		=> 7,
				),
				'expect_exception'	=> null,
			),
			array(
				'input'		=> array(
					'name'		=> 'Curt',
					'birthdate'	=> '22/03/1983',
					'score'		=> 9,
				),
				'expect'	=> array(
					'name'		=> 'CURT',
					'birthdate'	=> '1983-03-22',
					'score'		=> 9,
				),
				'expect_exception'	=> null,
			),
			array(
				'input'		=> array(
					'name'		=> 'Mike',
					'birthdate'	=> '01/01/2000',
					'score'		=> 'high',	# not allowed
				),
				'expect'	=> null,
				'expect_exception'	=> 'Parameter "score" validation check "types" failed for string value "high"',
			),
		);
		foreach ($tests as $i => $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_exception	= $test['expect_exception'];
			$got_exception = null;
			$validated_input = null;
			try {
				$validated_input = $validator->validate($input);
			}
			catch (Validate\ValidationException $e) {
				$got_exception = $e->getMessage();
			}
			$this->assertEquals($expect, $validated_input, "Test $i validate() returns expected result.");
			$this->assertEquals($expect_exception, $got_exception, "Test $i throws the expected exception.");
		}
	}

	public function testValidatePosNamedSpecs() {
		$specs = array(
			'name'	=> array(
				'type'			=> 'string',
				'max_length'	=> 2,
				'max_length'	=> 30,
				'after'	=> function(&$value) {
					if (is_string($value)) {
						$value = strtoupper($value);
					}
				},
			),
			'score' => array(
				'types' => array('float', 'integer'),
				'max_value'	=> 10,
				'min_value'	=> 0,
			),
		);
		$validator = new Validate\Validator(array(
			'specs'	=> $specs,
		));
		$tests = array(
			array(
				'input'		=> array('Jane', 7),
				'expect'	=> array('JANE', 7),
				'expect_exception'	=> null,
			),
			array(
				'input'		=> array('Mike', 'high'),
				'expect'	=> null,
				'expect_exception'	=> 'Parameter "1" validation check "types" failed for string value "high"',
			),
			array(
				'input'		=> array(),
				'expect'	=> null,
				'expect_exception'	=> 'Parameter "1" validation check "mandatory" failed for NULL value',
			),
		);
		foreach ($tests as $i => $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_exception	= $test['expect_exception'];
			$got_exception = null;
			$validated_input = null;
			try {
				$validated_input = $validator->validate_pos($input);
			}
			catch (Validate\ValidationException $e) {
				$got_exception = $e->getMessage();
			}
			$this->assertEquals($expect, $validated_input, "Test $i validate() returns expected result.");
			$this->assertEquals($expect_exception, $got_exception, "Test $i throws the expected exception.");
		}
	}

	public function testValidatePosUnnamedSpecs() {
		$specs = array(
			array(
				'type'			=> 'string',
				'max_length'	=> 2,
				'max_length'	=> 30,
				'after'	=> function(&$value) {
					if (is_string($value)) {
						$value = strtoupper($value);
					}
				},
			),
			array(
				'types' => array('float', 'integer'),
				'max_value'	=> 10,
				'min_value'	=> 0,
			),
		);
		$validator = new Validate\Validator(array(
			'specs'	=> $specs,
		));
		$tests = array(
			array(
				'input'		=> array('Jane', 7),
				'expect'	=> array('JANE', 7),
				'expect_exception'	=> null,
			),
			array(
				'input'		=> array('Mike', 'high'),
				'expect'	=> null,
				'expect_exception'	=> 'Parameter "1" validation check "types" failed for string value "high"',
			),
			array(
				'input'		=> array(),
				'expect'	=> null,
				'expect_exception'	=> 'Parameter "1" validation check "mandatory" failed for NULL value',
			),
		);
		foreach ($tests as $i => $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_exception	= $test['expect_exception'];
			$got_exception = null;
			$validated_input = null;
			try {
				$validated_input = $validator->validate_pos($input);
			}
			catch (Validate\ValidationException $e) {
				$got_exception = $e->getMessage();
			}
			$this->assertEquals($expect, $validated_input, "Test $i validate() returns expected result.");
			$this->assertEquals($expect_exception, $got_exception, "Test $i throws the expected exception.");
		}
	}

}



if (isset($argv)) {
	require_once(Test::FILE_NAME);
}
