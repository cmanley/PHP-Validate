<?php
/**
* Test script for Validator class.
*
* @author   Craig Manley
* @version  $Id: validator.php,v 1.1 2013/12/10 23:27:57 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../Validator.class.php');


$validator = new Validate\Validator(array(
	'allow_extra' => true,
	'specs' => array(
		'name'	=> array(
			'type'			=> 'string',
			'max_length'	=> 2,
			'max_length'	=> 30,
		),
		'birthdate' => array(
			'type'	=> 'string',
			'regex'	=> '#^[0-3]\d/[01]\d/\d{4}$#', // expect dd/mm/yyyy
			'after'	=> function(&$value) { // want yyyy-mm-dd
				if (is_string($value) && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $matches)) {
					$value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
				}
			},
			'optional' => true,
		),
		'score' => array(
			'types' => array('float', 'integer'),
			'max_value'	=> 10,
			'min_value'	=> 0,
		),
	),
));

$records = array(
	array(
		'name'		=> 'Jane',
		'birthdate'	=> '31/01/1984',
		'score'		=> 7,
	),
	array(
		'name'		=> 'Curt',
		'birthdate'	=> '22/03/1983',
		'score'		=> 9,
	),
);

$i = 0;
foreach ($records as $record) {
	print 'BEFORE: ' . print_r($record,true);
	try {
		$record = $validator->validate($record);
		print 'AFTER: ' . print_r($record,true) . "\n";
	}
	catch (Validate\ValidationException $e) {
		print 'Error at record ' . $i . ': ' . $e->getMessage() . "\n";
		continue;
	}
	$i++;
}
