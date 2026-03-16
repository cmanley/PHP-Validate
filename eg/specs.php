<?php
/**
* Example class that demonstrates the use of the SpecCollection class.
* The SpecCollection class is simply a collection of Spec objects.
* SpecCollection objects can be used as arrays since they implement the Countable, IteratorAggregate, and ArrayAccess interfaces.
*/
require_once(__DIR__ . '/../src/SpecCollection.php');


# A SpecCollection object can be created in 3 possible ways, all having the same effect.
# The contructor is given an associative array of name => Spec pairs


# This is the easy/lazy and my preferred way to create a SpecCollection object with it's embedded Spec objects.
$specs_easy = new Validate\SpecCollection([
	'firstname'	=> [
		'description'	=> 'First name',
		'mb_max_length'	=> 10,
		'regex'			=> '/^[A-Z][a-z]+$/',
		'type'			=> 'string',
	],
	'surname'	=> 1,	# shortcut for Spec with 'optional' => !value
	'age'		=> [
		'optional'		=> true,
		'mb_max_length'	=> 3,
		'regex'			=> '/^\d{1,3}$/',
		'types'			=> ['int', 'string'],
	],
]);


# This is the less lazy way to create a SpecCollection object with it's embedded Spec objects.
$specs_lazy = new Validate\SpecCollection([
	'firstname'	=> (new Validate\Spec([
		'description'	=> 'First name',
		'validation'	=> [
			'mb_max_length'	=> 10,
			'regex'			=> '/^[A-Z][a-z]+$/',
			'types'			=> ['string'],
		],
	])),
	'surname'	=> true,	# shortcut for Spec with 'optional' => !value
	'age'		=> (new Validate\Spec([
		'optional'		=> true,
		'validation'	=> [
			'mb_max_length'	=> 3,
			'regex'			=> '/^\d{1,3}$/',
			'types'			=> ['int', 'string'],
		],
	])),
]);


# This is the proper and most verbose way to create a SpecCollection object with it's embedded Spec objects.
$specs_proper = new Validate\SpecCollection([
	'firstname'	=> (new Validate\Spec([
		'description'	=> 'First name',
		'validation'	=> (new Validate\Validation([
			'mb_max_length'	=> 10,
			'regex'			=> '/^[A-Z][a-z]+$/',
			'types'			=> ['string'],
		])),
	])),
	'surname'	=> (new Validate\Spec([
	])),
	'age'		=> (new Validate\Spec([
		'optional'		=> true,
		'validation'	=> (new Validate\Validation([
			'mb_max_length'	=> 3,
			'regex'			=> '/^\d{1,3}$/',
			'types'			=> ['int', 'string'],
		])),
	])),
]);






# Check if all the Spec objects are indeed identical:
if (count(array_unique(array_map(fn($specs) => var_export($specs,true), [$specs_easy, $specs_lazy, $specs_proper] ))) != 1) {
	die("The SpecCollection objects are not identical!\n");
}



$specs = $specs_easy;


$tests = [
	'Test 1' => [
		'input'		=> [
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> 18,
		],
		'expect'	=> true,
	],
	'Test 2' => [
		'input'		=> [
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
		],
		'expect'	=> true,
	],
	'Test 3' => [
		'input'		=> [
			'surname'	=> 'Doe',
			'age'		=> 18,
		],
		'expect'	=> false,
	],
	'Test 4' => [
		'input'		=> [],
		'expect'	=> false,
	],
	'Test 5' => [
		'input'		=> [
			'firstname'	=> 'too_lazy',
			'surname'	=> 'Doe',
		],
		'expect'	=> false,
	],
	'Test 6' => [
		'input'		=> [
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> '',
		],
		'expect'	=> true,
	],
	'Test 7' => [
		'input'		=> [
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> null,
		],
		'expect'	=> true,
	],
];


$verbose = true;

foreach ($specs as $k => &$spec) {
	$spec = new Validate\Spec(['type' => 'float']);	# does nothing to $specs as the iterator works on a copy
	unset($spec);
}

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
