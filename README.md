PHP-Validate
============

PHP parameter/array/associative-array validation library. 
This is similar to Perl's [Params::Validate](http://search.cpan.org/~drolsky/Params-Validate-1.08/lib/Params/Validate.pm).

### Requirements:
*  PHP 5.3.0 or newer

### Usage:
All the classes contain PHP-doc documentation, so for now, take a look at the code of Validator.class.php or one of the test/example scripts in the t subdirectory.

**Example:**

		<?php
		require_once('lib/Validate/Validator.class.php');
	
		function save_student_score(array $params) {
			$params = (new Validate\Validator(array(
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
			)))->validate($params);
			// Insert $params into database here.
		}
			
		$error = null;
		try {
			save_student_score($_POST);
		}
		catch (Validate\ValidationException $e) {
			$error = $e->getMessage();
		}
		if ($error) {
			// display error message.
		}
		else {
			// display success message.
		}

	  


### Licensing
All of the code in this library is licensed under the MIT license as included in the LICENSE file
