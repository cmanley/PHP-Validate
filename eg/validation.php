<?php
/**
* Example script that demonstrates the use of the Validation class.
* The Validation class is rarely used stand-alone as it is only able to validate a single value.
*/
require_once(__DIR__ . '/../src/Validation.php');


$validation = new Validate\Validation([
	'mb_max_length'	=> 10,
	'regex'			=> '/a/',
	'callbacks'		=> [
		'is_lc'	=> function($s) { return strtolower($s) == $s; },
	],
]);
$tests = [
	[
		'data'		=> 'This is too long.',
		'expect'	=> false,
	],
	[
		'data'		=> 'this is also too long.',
		'expect'	=> false,
	],
	[
		'data'		=> 'Yaba',
		'expect'	=> false,
	],
	[
		'data'		=> 'gogo',
		'expect'	=> false,
	],
	[
		'data'		=> 'bla',
		'expect'	=> true,
	],
];

foreach ($tests as $test) {
	$data = $test['data'];
	print "Test: $data\n";
	print "\tValid?: " . (int) $validation->validate($data) . "\n";
	print "\tLast failure: " . print_r($validation->getLastFailure(),true) . "\n";
	print "\tRead max_length: " .($validation->max_length ? $validation->max_length : 'null') . "\n";
	print ($validation->validate($data) == $test['expect'] ? 'PASS' : 'FAIL') . "\n";
	print "\n";
}
