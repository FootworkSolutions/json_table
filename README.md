# JSON Table [![Build Status](https://travis-ci.org/FootworkSolutions/json_table.svg?branch=master)](https://travis-ci.org/FootworkSolutions/json_table) [![Code Climate](https://codeclimate.com/github/FootworkSolutions/json_table/badges/gpa.svg)](https://codeclimate.com/github/FootworkSolutions/json_table) [![Dependency Status](https://www.versioneye.com/user/projects/5644681d22c568002900005d/badge.svg?style=flat)](https://www.versioneye.com/user/projects/5644681d22c568002900005d)
A validator and storage library using the [JSON table schema](http://dataprotocols.org/json-table-schema/) written in PHP.

This is a great utility library for validating that CSV files, streams or any other data source validates against a predefined JSON table schema definition.

#### Limitations
Although this library has many great features, it does not (yet) adhere to the entire JSON table schema specification.

Currently the parts of the specification that are **NOT** supported are:

1. **Formats**:
	* array
	* binary (this is just validated as a string)
	* geopoint
	* geojson
2. **Constraints** currently there is no support for constraints.
3. **Foreign Keys**
	 * To meet our specific needs this has been built to reference a database table and not a data package as [outlined in the specification](http://dataprotocols.org/json-table-schema/#foreign-keys).

	The same schema structure is accepted but with the following considerations:
		* "datapackage" MUST be "postgresql".
		* "resource" MUST be the name of the table to use with optional schema qualifier. I.E. "import.t_table_name".
		* "fields" follow the JSON table schema specification but refer to the fields in the database table.


# Motivation
This library was written to fulfill a need for [Halo](http://www.halosystem.co.uk) a commercial client management system  maintained by [Footwork Solutions Ltd](https://github.com/FootworkSolutions).

It is used to validate and store data as part of a import tool for users of Halo.

We use a lot of open source projects at Footwork Solutions and this project, along with our other open source work is our way of giving back to the community we appreciate so much.

# Usage
See the [examples](https://github.com/FootworkSolutions/json_table/tree/master/examples) directory for a detailed run through of how to validate and store a CSV file.

#### Quick Start
Validate a CSV file:
```
// Instantiate the analysis class.
$lo_analyser = new \JsonTable\Analyse();

// Let the analyser know where the JSON table schema is.
$lo_analyser->set_schema('/your/file/path/example.json');

// Let the analyser know where the CSV file to validate is.
$lo_analyser->set_file('/your/file/path/example.csv');

// Check whether the file is valid against the schema.
$lb_file_is_valid = $lo_analyser->analyse();
```
Store the valid CSV file:
```
$lo_store = \JsonTable\Store::load('postgresql');
$lb_file_is_stored = $lo_store->store('your_table_name');
```

# Installation
```
composer require footwork_solutions/json_table
```

# Implementation Notes

* datetime; date; time - fmt:[PATTERN] - This supports PHP date formats @see http://php.net/manual/en/datetime.formats.date.php
* datetime; date; time - ISO8610 - The following formats are validated without specifing a format:
	* Combined date and time in UTC: 2015-03-09T13:07:04Z
	* Date: 2015-03-09
	* Time: hh:mm:ss
* Given the range of options that match the ISO8610 format, we recommend that you always specify a format.
* Pattens: A delimiter must be included in the regex.
* Column name checking is case insensitive.
* Foreign keys with a single field can be omitted from the CSV and the foreign key check will be ignored. However, foreign keys with multiple fields must have all those fields in the CSV file.

# Contributors
#### Bugs & Feature Requests
Please use the [issue tracker](https://github.com/FootworkSolutions/json_table/issues) to report any bugs or request new features.

#### Developing
Pull Requests are always welcome.

Please ensure that your code meets the [PSR-1](http://www.php-fig.org/psr/psr-1), [PSR-2](http://www.php-fig.org/psr/psr-2) and  [PSR-4](http://www.php-fig.org/psr/psr-4) standards.

1. [Fork](https://github.com/FootworkSolutions/json_table/fork) the repository.
2. Create your feature branch (git checkout -b my-new-feature).
3. Commit your changes.
4. Push to the branch (git push origin my-new-feature).
5. Create a new Pull Request.

# Licence
This is free software distributed under the terms of the MIT license. See [LICENCE](https://github.com/FootworkSolutions/json_table/blob/master/LICENCE) for the full details.
