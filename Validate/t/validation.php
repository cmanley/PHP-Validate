<?php
/**
* Test script for Validation class.
*
* @author   Craig Manley
* @version  $Id: validation.php,v 1.1 2013/12/10 23:27:57 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../Validation.class.php');


$o = new Validate\Validation(array(
	'max_length'	=> 10,
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
	print "\tValid?: " . (int) $o->validate($data) . "\n";
	print "\tLast failure: " . print_r($o->getLastFailure(),true) . "\n";
	print "\tRead max_length: " .($o->max_length ? $o->max_length : 'null') . "\n";
	print ($o->validate($data) == $test['expect'] ? 'PASS' : 'FAIL') . "\n";
	print "\n";
}
