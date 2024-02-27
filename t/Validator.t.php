<?php declare(strict_types=1);
namespace PHPUnit\Framework;
if (isset($argv)) {
	print "Usage:\n";
	print 'phpunit ' . $argv[0] . "\n";
	class TestCase {}
}


class T extends TestCase {

	const CLASS_NAME = 'Validate\\Validator';
	const FILE_NAME = '../src/Validator.php';

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
			'delete_null'	=> false,
			'null_empty_strings'	=> true,
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
		$validator = new \Validate\Validator(array(
			'allow_extra'	=> false,
			'delete_null'	=> true,
			'null_empty_strings'	=> true,
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
				'expect_exception'	=> 'Parameter "score" failed validation "types" for string value "high"',
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
			catch (\Validate\Exception\ValidationException $e) {
				$got_exception = $e->getMessage();
			}
			$this->assertEquals($expect, $validated_input, "Test $i validate() returns expected result.");
			$this->assertEquals($expect_exception, $got_exception, "Test $i throws the expected exception.");
		}
	}

	public function testValidatePosSpecCollection() {
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
			'score' => array(	# string keys are allowed too.
				'types' => array('float', 'integer'),
				'max_value'	=> 10,
				'min_value'	=> 0,
			),
		);
		$inputs = array(
			'normal'			=> array('Jane', 7),
			'too few params'	=> array('Jane'),
			'too many params'	=> array('Jane', 7, '', 'bla'),
			'invalid 2nd param (index 1)' => array('Mike', 'high'),
			'no params'			=> [],
		);
		$tests = array(
			'no options' => array(
				'options' => [],
				'expects' => array(
					'normal'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'too few params'	=> array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "mandatory" for NULL value',
					),
					'too many params'	=> array(
						'expect'			=> null,
						'expect_exception'	=> 'Too many arguments given (4) for the number of specs (2)',
					),
					'invalid 2nd param (index 1)' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "types" for string value "high"',
					),
					'no params' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "0" failed validation "mandatory" for NULL value',
					),
				),
			),
			'allow_extra is true' => array(
				'options' => array(
					'allow_extra'	=> true,
				),
				'expects' => array(
					'normal'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'too few params'	=> array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "mandatory" for NULL value',
					),
					'too many params'	=> array(
						'expect'			=> array('JANE', 7, '', 'bla'),
						'expect_exception'	=> null,
					),
					'invalid 2nd param (index 1)' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "types" for string value "high"',
					),
					'no params' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "0" failed validation "mandatory" for NULL value',
					),
				),
			),
			'remove_extra is true' => array(
				'options' => array(
					'remove_extra'	=> true,
				),
				'expects' => array(
					'normal'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'too few params'	=> array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "mandatory" for NULL value',
					),
					'too many params'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'invalid 2nd param (index 1)' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "types" for string value "high"',
					),
					'no params' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "0" failed validation "mandatory" for NULL value',
					),
				),
			),
			'allow_extra is true and remove_extra is true' => array(
				'options' => array(
					'allow_extra'	=> true,
					'remove_extra'	=> true,
				),
				'expects' => array(
					'normal'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'too few params'	=> array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "mandatory" for NULL value',
					),
					'too many params'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'invalid 2nd param (index 1)' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "types" for string value "high"',
					),
					'no params' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "0" failed validation "mandatory" for NULL value',
					),
				),
			),
			'allow_extra is true and null_empty_strings is true' => array(
				'options' => array(
					'allow_extra'	=> true,
					'null_empty_strings'	=> true,
				),
				'expects' => array(
					'normal'	=> array(
						'expect'			=> array('JANE', 7),
						'expect_exception'	=> null,
					),
					'too few params'	=> array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "mandatory" for NULL value',
					),
					'too many params'	=> array(
						'expect'			=> array('JANE', 7, null, 'bla'),
						'expect_exception'	=> null,
					),
					'invalid 2nd param (index 1)' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "1" failed validation "types" for string value "high"',
					),
					'no params' => array(
						'expect'			=> null,
						'expect_exception'	=> 'Parameter "0" failed validation "mandatory" for NULL value',
					),
				),
			),
		);
		foreach ($tests as $name => $test) {
			$args = $test['options'];
			$args['specs'] = $specs;
			$validator = new \Validate\Validator($args);
			foreach ($inputs as $input_name => $input) {
				$expect	= $test['expects'][$input_name]['expect'];
				$expect_exception = $test['expects'][$input_name]['expect_exception'];
				$got_exception = null;
				$validated_input = null;
				try {
					$validated_input = $validator->validate_pos($input);
				}
				catch (\Validate\Exception\ValidationException $e) {
					$got_exception = $e->getMessage();
				}
				$this->assertEquals($expect, $validated_input, "Test \"$name\"->\"$input_name\" validate() returns expected result.");
				$this->assertEquals($expect_exception, $got_exception, "Test \"$name\"->\"$input_name\" throws the expected exception.");
			}
		}
	}

}



if (isset($argv)) {
	require_once(T::FILE_NAME);
}
