<?php
/**
* Test script for Spec class.
*
* @author   Craig Manley
* @version  $Id: spec_lazy.php,v 1.1 2013/12/10 23:27:57 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../Spec.class.php');


$o = new Validate\Spec(array(
	'allow_empty'	=> false,
	'description'	=> 'String with a lowercase "a"',
	'optional'		=> false,
	'validation'	=> array(
		'max_length'	=> 10,
		'regex'			=> '/a/',
		'callbacks'		=> array(
			'is_lc'	=> function($s) { return strtolower($s) == $s; },
		),
	),
));

$tests = array(
	'This is too long.' => array(
		'data'		=> 'This is too long.',
		'expect'	=> false,
	),
	'this is also too long.' => array(
		'data'		=> 'this is also too long.',
		'expect'	=> false,
	),
	'Yaba' => array(
		'data'		=> 'Yaba',
		'expect'	=> false,
	),
	'gogo' => array(
		'data'		=> 'gogo',
		'expect'	=> false,
	),
	'bla' => array(
		'data'		=> 'bla',
		'expect'	=> true,
	),
	'null'	=> array(
		'data'		=> null,
		'expect'	=> false,
	),
	'empty string'	=> array(
		'data'		=> '',
		'expect'	=> false,
	),
);

print "Read optional: " . ((int)$o->optional) . "\n";
foreach ($tests as $name => $test) {
	$data = $test['data'];
	print "Test: $name\n";
	print "\tValid?: " . (int) $o->validate($data) . "\n";
	print "\tLast failure: " . print_r($o->getLastFailure(),true) . "\n";
	print ($o->validate($data) == $test['expect'] ? 'PASS' : 'FAIL') . "\n";
	print "\n";
}
