PHP-Validate
============

PHP validation library.
Inspired by [Params::Validate](http://search.cpan.org/perldoc/Params::Validate) for Perl.
You can use it to validate almost anything, but typically it can be used for (strictly) validating input from HTML forms, fields while reading CSV files, function parameters, etc.

### Requirements
*  PHP 5.4 or later

### Installation
Download or checkout from git, or install the packagist package cmanley/validate.

**WARNING**: This is a still a work in early progress and the API can change at any time. So use fixed releases and don't download from the master branch unless you're prepared to fix things yourself.

### Synopsis

**Example of function associative array parameter validation:**
```php
<?php
require_once('/path/to/Validate/Validator.php'); # or use composer autoloader

function save_student_score(array $params) {
	# Create the validator object. By using static, it is only created once and persists for all function calls.
	$validator = new \Validate\Validator([
		'trim' => true, # trim all string values
		'null_empty_strings' => true, # convert empty string values to null
		'specs' => [
			'name'	=> [
				'type' => 'string',
				'mb_max_length'	=> 2,
				'mb_max_length'	=> 30,
			],
			'birthdate' => [
				'type'  => 'string',
				'regex' => '#^[0-3]\d/[01]\d/\d{4}$#', # expect dd/mm/yyyy
				'after' => function(&$value) { # want yyyy-mm-dd
					if (is_string($value) && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $matches)) {
						$value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
					}
				},
				'optional' => true,
			],
			'score' => [
				'types'     => ['float', 'integer'],
				'max_value' => 10,
				'min_value' => 0,
			],
		],
	]);

	# Actually validate the parameters. This will throw an exception in invalid parameters are found.
	$params = $validator->validate($params);

	# Insert $params into database here.

}
	
$error = null;
try {
	save_student_score($_POST);
}
catch (Validate\Exception\ValidationException $e) {
	$error = $e->getMessage();
}
if ($error) {
	# display error message.
}
else {
	# display success message.
}
```

See the Wiki for API documentation.

### Licensing
All of the code in this library is licensed under the MIT license as included in the LICENSE file
