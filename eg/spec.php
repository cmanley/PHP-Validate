<?php
/**
* Example class that demonstrates the use of the Spec class.
* A Spec class contains a Validation class as well as some extra attributes.
* See it's contructor documentation for all the possible parameters.
* The Spec class is rarely used stand-alone as it is only able to validate a single value.
*/
require_once(__DIR__ . '/../src/Spec.php');

# A Spec object can be created in 3 possible ways, all having the same effect.
$specs = [];


# This is the proper way to create a Spec object with it's embedded Validation object.
$specs []= new Validate\Spec([
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,
	'validation'	=> (new Validate\Validation([
		'mb_max_length'	=> 10,
		'regex'			=> '/a/',
		'callbacks'		=> [
			'is_lc'	=> fn($s) => mb_strtolower($s) == $s,
		],
	])),
]);


# This is the lazy way to create a Spec object. It'll automatically convert the 'validation' array into a Validation object internally.
$specs []= new Validate\Spec([
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,
	'validation'	=> [
		'mb_max_length'	=> 10,
		'regex'			=> '/a/',
		'callbacks'		=> [
			'is_lc'	=> fn($s) => mb_strtolower($s) == $s,
		],
	],
]);


# This is the very lazy way to create a Spec object. It'll automatically create a Validation object internally.
$specs []= new Validate\Spec([
	# Spec options:
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,

	# Validation options:
	'mb_max_length'	=> 10,
	'regex'			=> '/a/',
	'callbacks'		=> [
		'is_lc'	=> fn($s) => mb_strtolower($s) == $s,
	],
]);


# Check if all the Spec objects are indeed identical:
if (count(array_unique(array_map(fn($spec) => var_export($spec,true), $specs))) != 1) {
	die("The Spec objects are not identical!\n");
}



$tests = [
	'This is too long.' => [
		'input'		=> 'This is too long.',
		'expect'	=> false,
	],
	'this is also too long.' => [
		'input'		=> 'this is also too long.',
		'expect'	=> false,
	],
	'Yaba' => [
		'input'		=> 'Yaba',
		'expect'	=> false,
	],
	'gogo' => [
		'input'		=> 'gogo',
		'expect'	=> false,
	],
	'bla' => [
		'input'		=> 'bla',
		'expect'	=> true,
	],
	'null'	=> [
		'input'		=> null,
		'expect'	=> false,
	],
	'empty string'	=> [
		'input'		=> '',
		'expect'	=> false,
	],
];


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
