<?php
/**
* Example script that demonstrates the use of the 'after' function in a spec.
* The 'after' function is executed after other spec validations have been performed.
*
* @author   Craig Manley
* @version  $Id: validator_after.php,v 1.3 2016/06/13 20:04:08 cmanley Exp $
* @package  Validate
*/
require_once(__DIR__ . '/../src/Validator.php');


# Define the validation specs
$specs = array(
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
);


# Create a Validator
$validator = new Validate\Validator(array(
	'specs' => $specs,
));


# Define records to test
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


# Validate the records
$i = 0;
foreach ($records as $record) {
	print 'Before validation: ' . print_r($record,true);
	try {
		$record = $validator->validate($record);
		print 'After validation: ' . print_r($record,true);
	}
	catch (Validate\ValidationException $e) {
		print 'Error at record ' . $i . ': ' . $e->getMessage() . "\n";
		continue;
	}
	print "\n\n";
	$i++;
}
