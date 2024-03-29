<?php declare(strict_types=1);
namespace PHPUnit\Framework;
if (isset($argv)) {
	print "Usage:\n";
	print 'phpunit ' . $argv[0] . "\n";
	class TestCase {}
}


class T extends TestCase {

	const CLASS_NAME = 'Validate\\Spec';
	const FILE_NAME = '../src/Spec.php';

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

			'allow_empty',
			'description',
			'before',
			'after',
			'optional',
			'trim',
			'validation',

			'getDefault',
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
			'allow_empty'	=> false,
			'before'		=> function(&$x) { if (is_string($x)) { $x = mb_strtolower($x); } },
			'after'			=> function(&$x) { if (is_string($x)) { $x = mb_strtolower($x); } },
			'default'		=> 'nobody@home.com',
			'description'	=> 'Email address',
			'optional'		=> true,
			'validation'	=> new \Validate\Validation(array(
				'type'			=> 'string',
				'callback'		=> function($x) { return filter_var($x, FILTER_VALIDATE_EMAIL); },
				'mb_max_length'	=> 50,
			)),
			'_bla'			=> 'Options starting with an underscore should be silently ignored.',
		));
		$this->assertTrue(is_object($o), 'Create object with all supported parameters.');
		$this->assertEquals(false, $o->allow_empty, 'Read an attribute');
	}

	public function testValidate() {
		$spec = new \Validate\Spec(array(
			'allow_empty'	=> false,
			'before'		=> function(&$x) { if (is_string($x)) { $x = mb_strtolower($x); } },
			'after'			=> function(&$x) { if (is_string($x)) { $x = ucfirst($x); } },
			'description'	=> 'Email address',
			'validation'	=> new \Validate\Validation(array(
				'type'			=> 'string',
				'callbacks'		=> array(
					'syntax'		=> function($x) { return filter_var($x, FILTER_VALIDATE_EMAIL); },
					'no_hotmail'	=> function($x) { return !preg_match('/@hotmail\.com$/', $x); },
				),
				'mb_max_length'	=> 50,
			)),
		));
		$tests = array(
			array(
				'input'		=> 'user@hotmail.com',
				'expect'	=> false,
				'expect_last_failure'	=> 'no_hotmail (callback)',
			),
			array(
				'input'		=> 'bgates@microsoftcom',
				'expect'	=> false,
				'expect_last_failure'	=> 'syntax (callback)',
			),
			array(
				'input'		=> 'user@outlook.com',
				'expect'	=> true,
				'expect_last_failure'	=> null,
			),
			array(
				'input'		=> null,
				'expect'	=> false,
				'expect_last_failure'	=> 'mandatory',
			),
			array(
				'input'		=> 'billy_bob_gates_the_boss@at_a_very_very_long_domain_name.com',
				'expect'	=> false,
				'expect_last_failure'	=> 'mb_max_length',
			),
			array(
				'input'		=> 12345,
				'expect'	=> false,
				'expect_last_failure'	=> 'types',
			),
		);
		foreach ($tests as $k => $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_last_failure	= $test['expect_last_failure'];
			$this->assertEquals($expect, $spec->validate($input), "validate(\"$input\") returns expected result.");
			$this->assertEquals($expect_last_failure, $spec->getLastFailure(), "getLastFailure() returns expected result for test $k value \"$input\".");
		}
	}

	public function testValidateWithDefault() {
		$spec = new \Validate\Spec(array(
			'allow_empty'	=> false,
			'after'			=> function(&$x) { $x = mb_strtolower($x); },
			'description'	=> 'Email address',
			'default'		=> 'nobody',	# defaults aren't validated as they are trusted values
			'validation'	=> new \Validate\Validation(array(
				'type'			=> 'string',
				'callbacks'		=> array(
					'syntax'		=> function($x) { return filter_var($x, FILTER_VALIDATE_EMAIL); },
					'no_hotmail'	=> function($x) { return !preg_match('/@hotmail\.com$/', $x); },
				),
				'mb_max_length'	=> 50,
			)),
		));
		$tests = array(
			array(
				'input'		=> 'user@outlook.com',
				'expect'	=> true,
				'expect_last_failure'	=> null,
			),
			array(
				'input'		=> null,
				'expect'	=> true,
				'expect_last_failure'	=> null,
			),
			array(
				'input'		=> '',
				'expect'	=> true,
				'expect_last_failure'	=> null,
			),
		);
		foreach ($tests as $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_last_failure	= $test['expect_last_failure'];
			$copy_of_input = $input;
			$this->assertEquals($expect, $spec->validate($copy_of_input), "validate(\"$input\") returns expected result.");
			$this->assertTrue((isset($input) && strlen($input)) || ($copy_of_input === $spec->default), 'Default value is applied only when input is null.');
			$this->assertEquals($expect_last_failure, $spec->getLastFailure(), "getLastFailure() returns expected result for \"$input\".");
		}
	}

	public function testReturnValuesBeforeAfter() {
		foreach (array('before', 'after') as $when) {
			$spec = new \Validate\Spec(array(
				$when			=> function(&$x) {},	# void (null) result
			));
			$input = 'anything';
			$this->assertTrue($spec->validate($input), "Using callback '$when' with void return value validates as true.");
			$this->assertEquals(null, $spec->getLastFailure(), 'Check last failure');

			$spec = new \Validate\Spec(array(
				$when			=> function(&$x) { return false; },
			));
			$input = 'anything';
			$this->assertTrue(!$spec->validate($input), "Using callback '$when' with FALSE return value validates as FALSE.");
			$this->assertEquals("callback $when", $spec->getLastFailure(), 'Check last failure');

			$spec = new \Validate\Spec(array(
				$when			=> function(&$x) { $x = strtoupper($x); return true; },
			));
			$input = 'anything';
			$this->assertTrue($spec->validate($input), "Using callback '$when' with TRUE return value validates as TRUE.");
			$this->assertEquals($input, 'ANYTHING', "'$when' callback modified variable passed by reference.");
		}
	}

}



if (isset($argv)) {
	require_once(__DIR__ . '/' . T::FILE_NAME);
	if (1) {
		$spec = new \Validate\Spec(array(
			'allow_empty'	=> false,
			'before'		=> function(&$x) { if (is_string($x)) { $x = mb_strtolower($x); } },
			'after'			=> function(&$x) { if (is_string($x)) { $x = ucfirst($x); } },
			'description'	=> 'Email address',
			'validation'	=> new \Validate\Validation(array(
				'type'			=> 'string',
				'callbacks'		=> array(
					'syntax'		=> function($x) { return filter_var($x, FILTER_VALIDATE_EMAIL); },
					'no_hotmail'	=> function($x) { return !preg_match('/@hotmail\.com$/', $x); },
				),
				'mb_max_length'	=> 50,
			)),
		));
		$tests = array(
			array(
				'input'		=> 'user@hotmail.com',
				'expect'	=> false,
				'expect_last_failure'	=> 'no_hotmail (callback)',
			),
			array(
				'input'		=> 'bgates@microsoftcom',
				'expect'	=> false,
				'expect_last_failure'	=> 'syntax (callback)',
			),
			array(
				'input'		=> 'user@outlook.com',
				'expect'	=> true,
				'expect_last_failure'	=> null,
			),
			array(
				'input'		=> null,
				'expect'	=> false,
				'expect_last_failure'	=> 'mandatory',
			),
			array(
				'input'		=> 'billy_bob_gates_the_boss@at_a_very_very_long_domain_name.com',
				'expect'	=> false,
				'expect_last_failure'	=> 'mb_max_length',
			),
			array(
				'input'		=> 12345,
				'expect'	=> false,
				'expect_last_failure'	=> 'types',
			),
		);
		foreach ($tests as $test) {
			$input	= $test['input'];
			$expect	= $test['expect'];
			$expect_last_failure	= $test['expect_last_failure'];
			print 'validate(' . json_encode($input) . "): ";
			$result = $spec->validate($input);
			print json_encode($result);
			if (!$result) {
				print '	getLastFailure()=' . $spec->getLastFailure();
			}
			print "\n";
		}
	}
}
