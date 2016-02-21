<?php
/**
* Example class that demonstrates the use of the Spec class.
* A Spec class contains a Validation class as well as some extra attributes.
* See it's contructor documentation for all the possible parameters.
* The Spec class is rarely used stand-alone as it is only able to validate a single value.
*
* @author   Craig Manley
* @version  $Id: spec.php,v 1.2 2016/02/17 23:04:58 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../src/Spec.class.php');

// A Spec object can be created in 3 possible ways, all having the same effect.
$specs = array();


// This is the proper way to create a Spec object with it's embedded Validation object.
$specs []= new Validate\Spec(array(
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,
	'validation'	=> (new Validate\Validation(array(
		'mb_max_length'	=> 10,
		'regex'			=> '/a/',
		'callbacks'		=> array(
			'is_lc'	=> function($s) { return mb_strtolower($s) == $s; },
		),
	))),
));


// This is the lazy way to create a Spec object. It'll automatically convert the 'validation' array into a Validation object internally.
$specs []= new Validate\Spec(array(
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,
	'validation'	=> array(
		'mb_max_length'	=> 10,
		'regex'			=> '/a/',
		'callbacks'		=> array(
			'is_lc'	=> function($s) { return mb_strtolower($s) == $s; },
		),
	),
));


// This is the very lazy way to create a Spec object. It'll automatically create a Validation object internally.
$specs []= new Validate\Spec(array(
	// Spec options:
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,

	// Validation options:
	'mb_max_length'	=> 10,
	'regex'			=> '/a/',
	'callbacks'		=> array(
		'is_lc'	=> function($s) { return mb_strtolower($s) == $s; },
	),
));


// Check if all the Spec objects are indeed identical:
if (count(array_unique(array_map(function($spec) { return var_export($spec,true); }, $specs))) != 1) {
	die("The Spec objects are not identical!\n");
}



$tests = array(
	'This is too long.' => array(
		'input'		=> 'This is too long.',
		'expect'	=> false,
	),
	'this is also too long.' => array(
		'input'		=> 'this is also too long.',
		'expect'	=> false,
	),
	'Yaba' => array(
		'input'		=> 'Yaba',
		'expect'	=> false,
	),
	'gogo' => array(
		'input'		=> 'gogo',
		'expect'	=> false,
	),
	'bla' => array(
		'input'		=> 'bla',
		'expect'	=> true,
	),
	'null'	=> array(
		'input'		=> null,
		'expect'	=> false,
	),
	'empty string'	=> array(
		'input'		=> '',
		'expect'	=> false,
	),
);


$spec = reset($specs);
print "Read attribute \"optional\": " . ((int)$spec->optional) . "\n";
print "Read attribute \"description\": " . $spec->description . "\n";
foreach ($tests as $name => $test) {
	$data = $test['input'];
	print "Test: $name\n";
	print "	Valid?: " . (int) $spec->validate($data) . "\n";
	print "	Last failure: " . $spec->getLastFailure() . "\n";
	print "	" . ($spec->validate($data) == $test['expect'] ? 'PASS' : 'FAIL') . "\n";
	print "\n\n";
}
