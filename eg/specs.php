<?php
/**
* Example class that demonstrates the use of the Specs class.
* The Specs class is simply a collection of Spec objects.
* Specs objects can be used as arrays since they implement the Countable, IteratorAggregate, and ArrayAccess interfaces.
*
* @author   Craig Manley
* @version  $Id: specs.php,v 1.1 2016/02/16 02:54:43 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../src/Specs.class.php');


// A Specs object can be created in 3 possible ways, all having the same effect.
// The contructor is given an associative array of name => Spec pairs


// This is the easy/lazy and my preferred way to create a Specs object with it's embedded Spec objects.
$specs_easy = new Validate\Specs(array(
	'firstname'	=> array(
		'description'	=> 'First name',
		'mb_max_length'	=> 10,
		'regex'			=> '/^[A-Z][a-z]+$/',
		'type'			=> 'string',
	),
	'surname'	=> 1,	// shortcut for Spec with 'optional' => !value
	'age'		=> array(
		'optional'		=> true,
		'mb_max_length'	=> 3,
		'regex'			=> '/^\d{1,3}$/',
		'types'			=> array('int', 'string'),
	),
));


// This is the less lazy way to create a Specs object with it's embedded Spec objects.
$specs_lazy = new Validate\Specs(array(
	'firstname'	=> (new Validate\Spec(array(
		'description'	=> 'First name',
		'validation'	=> array(
			'mb_max_length'	=> 10,
			'regex'			=> '/^[A-Z][a-z]+$/',
			'types'			=> array('string'),
		),
	))),
	'surname'	=> true,	// shortcut for Spec with 'optional' => !value
	'age'		=> (new Validate\Spec(array(
		'optional'		=> true,
		'validation'	=> array(
			'mb_max_length'	=> 3,
			'regex'			=> '/^\d{1,3}$/',
			'types'			=> array('int', 'string'),
		),
	))),
));


// This is the proper and most verbose way to create a Specs object with it's embedded Spec objects.
$specs_proper = new Validate\Specs(array(
	'firstname'	=> (new Validate\Spec(array(
		'description'	=> 'First name',
		'validation'	=> (new Validate\Validation(array(
			'mb_max_length'	=> 10,
			'regex'			=> '/^[A-Z][a-z]+$/',
			'types'			=> array('string'),
		))),
	))),
	'surname'	=> (new Validate\Spec(array(
	))),
	'age'		=> (new Validate\Spec(array(
		'optional'		=> true,
		'validation'	=> (new Validate\Validation(array(
			'mb_max_length'	=> 3,
			'regex'			=> '/^\d{1,3}$/',
			'types'			=> array('int', 'string'),
		))),
	))),
));






// Check if all the Spec objects are indeed identical:
if (count(array_unique(array_map(function($specs) { return var_export($specs,true); }, array($specs_easy, $specs_lazy, $specs_proper) ))) != 1) {
	die("The Specs objects are not identical!\n");
}



$specs = $specs_easy;


$tests = array(
	'Test 1' => array(
		'input'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> 18,
		),
		'expect'	=> true,
	),
	'Test 2' => array(
		'input'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
		),
		'expect'	=> true,
	),
	'Test 3' => array(
		'input'		=> array(
			'surname'	=> 'Doe',
			'age'		=> 18,
		),
		'expect'	=> false,
	),
	'Test 4' => array(
		'input'		=> array(),
		'expect'	=> false,
	),
	'Test 5' => array(
		'input'		=> array(
			'firstname'	=> 'too_lazy',
			'surname'	=> 'Doe',
		),
		'expect'	=> false,
	),
	'Test 6' => array(
		'input'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> '',
		),
		'expect'	=> true,
	),
	'Test 7' => array(
		'input'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> null,
		),
		'expect'	=> true,
	),
);


$verbose = true;
foreach ($tests as $name => $test) {
	$input = $test['input'];
	$verbose && print "Test: $name\n";
	$valid = true;
	foreach ($specs as $k => $spec) {
		$v = @$input[$k];
		$verbose && print "\t$k\n";
		$verbose && print "\t\tvalid?: " . (int) $spec->validate($v) . "\n";
		$verbose && print "\t\tlast failure: " . print_r($spec->getLastFailure(),true) . "\n";
		$valid = $valid && $spec->validate($v);
	}
	$pass = $valid == $test['expect'];
	$verbose && print "\t" . ($pass ? 'PASS' : 'FAIL') . "\n";
	$verbose && print "\n";
}
