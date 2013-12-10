<?php
/**
* Test script for Specs class.
*
* @author   Craig Manley
* @version  $Id: specs.php,v 1.1 2013/12/10 23:27:57 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../Specs.class.php');


$specs_typical = new Validate\Specs(array(
	'firstname'	=> (new Validate\Spec(array(
		'max_length'	=> 10,
		'regex'			=> '/^[A-Z][a-z]+$/',
		'types'			=> array('string'),
	))),
	'surname'			=> 1,
	'age'	=> (new Validate\Spec(array(
		'max_length'	=> 3,
		'regex'			=> '/^\d{1,3}$/',
		'types'			=> array('scalar'),
		'optional'		=> true,
	))),
));

$specs_lazy = new Validate\Specs(array(
	'firstname'	=> array(
		'max_length'	=> 10,
		'regex'			=> '/^[A-Z][a-z]+$/',
		'types'			=> array('string'),
	),
	'surname' => true,
	'age'	=> array(
		'max_length'	=> 3,
		'regex'			=> '/^\d{1,3}$/',
		'types'			=> array('scalar'),
		'optional'		=> true,
	),
));

$specs_map = array(
	'Specs typical'	=> $specs_typical,
	'Specs lazy'	=> $specs_lazy,
);

$tests = array(
	'Test 1' => array(
		'args'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> 18,
		),
		'expect'	=> true,
	),
	'Test 2' => array(
		'args'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
		),
		'expect'	=> true,
	),
	'Test 3' => array(
		'args'		=> array(
			'surname'	=> 'Doe',
			'age'		=> 18,
		),
		'expect'	=> false,
	),
	'Test 4' => array(
		'args'		=> array(),
		'expect'	=> false,
	),
	'Test 5' => array(
		'args'		=> array(
			'firstname'	=> 'too_lazy',
			'surname'	=> 'Doe',
		),
		'expect'	=> false,
	),
	'Test 6' => array(
		'args'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> '',
		),
		'expect'	=> true,
	),
	'Test 7' => array(
		'args'		=> array(
			'firstname'	=> 'Jane',
			'surname'	=> 'Doe',
			'age'		=> null,
		),
		'expect'	=> true,
	),
);

$verbose = false;
foreach ($specs_map as $specs_name => $specs) {
	print "Testing specs $specs_name\n";
	$all_pass = true;
	foreach ($tests as $name => $test) {
		$args = $test['args'];
		$verbose && print "Test: $name\n";
		$valid = true;
		foreach ($specs as $k => $spec) {
			$v = @$args[$k];
			$verbose && print "\t$k\n";
			$verbose && print "\t\tvalid?: " . (int) $spec->validate($v) . "\n";
			$verbose && print "\t\tlast failure: " . print_r($spec->getLastFailure(),true) . "\n";
			$valid = $valid && $spec->validate($v);
		}
		$pass = $valid == $test['expect'];
		$all_pass = $all_pass && $pass;
		$verbose && print "\t" . ($pass ? 'PASS' : 'FAIL') . "\n";
		$verbose && print "\n";
	}
	print "\t" . ($all_pass ? 'PASS' : 'FAIL') . "\n";
}
