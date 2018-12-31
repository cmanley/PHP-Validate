<?php
/**
* Example script that demonstrates the use of the Validation class.
* The Validation class is rarely used stand-alone as it is only able to validate a single value.
*/
require_once(__DIR__ . '/../src/Validation.php');


$validation = new Validate\Validation(array(
	'mb_max_length'	=> 10,
	'regex'			=> '/a/',
	'callbacks'		=> array(
		'is_lc'	=> function($s) { return strtolower($s) == $s; },
	),
));
$tests = array(
	array(
		'data'		=> 'This is too long.',
		'expect'	=> false,
	),
	array(
		'data'		=> 'this is also too long.',
		'expect'	=> false,
	),
	array(
		'data'		=> 'Yaba',
		'expect'	=> false,
	),
	array(
		'data'		=> 'gogo',
		'expect'	=> false,
	),
	array(
		'data'		=> 'bla',
		'expect'	=> true,
	),
);

foreach ($tests as $test) {
	$data = $test['data'];
	print "Test: $data\n";
	print "\tValid?: " . (int) $validation->validate($data) . "\n";
	print "\tLast failure: " . print_r($validation->getLastFailure(),true) . "\n";
	print "\tRead max_length: " .($validation->max_length ? $validation->max_length : 'null') . "\n";
	print ($validation->validate($data) == $test['expect'] ? 'PASS' : 'FAIL') . "\n";
	print "\n";
}
