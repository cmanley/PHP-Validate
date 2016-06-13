<?php
/**
* Example script that demonstrates the use of the 'before' function in a spec.
* The 'before' function is executed before other spec validations have been performed.
*
* @author   Craig Manley
* @version  $Id: validator_before.php,v 1.3 2016/06/13 20:04:08 cmanley Exp $
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
		'regex'	=> '/^\d{4}-[01]\d-[0-3]\d$/', // should be yyyy-mm-dd
		'before'	=> function(&$value) { // expect dd/mm/yyyy
			if (is_string($value) && preg_match('#^([0-3]\d)/([01]\d)/(\d{4})$#', $value, $matches)) {
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

