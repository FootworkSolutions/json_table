/**
 * Example SQL file.
 * Run this file to set up your postgreSQL database to work with the example.php script.
 * This will add the tables required to validate the foreign keys and store the imported data.
 *
 * The permissions here expect your database to be named json_test but this can easily be updated
 * to your own database by replacing all references to json_test below.
 *
 * @package JSON table
 */
BEGIN;
	-- Add the table to hold foreign key details.
	-- DROP TABLE IF EXISTS json_table_foreign_key_test;
	CREATE TABLE json_table_foreign_key_test (
		  id SERIAL PRIMARY KEY
		, email VARCHAR
		, first_name VARCHAR
		, user_website VARCHAR
	) with oids;

	ALTER TABLE json_table_foreign_key_test OWNER TO postgres;
	GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE json_table_foreign_key_test TO json_test;
	GRANT SELECT, UPDATE ON TABLE json_table_foreign_key_test_id_seq TO json_test;
	COMMENT ON TABLE json_table_foreign_key_test IS 'This table is for testing foreign keys in the JSON table system.';


	-- Add some foreign keys to test against.
	INSERT INTO json_table_foreign_key_test (email, first_name, user_website) VALUES
	('tom.jones@gmail.com', 'Tom', 'www.tomjones.com'),
	('kirt.cobain@gmail.com', 'Kirt', 'http://en.wikipedia.org/wiki/Kurt_Cobain');


	-- Add the table to store the example data.
	-- DROP TABLE IF EXISTS json_table_stored_data_test;
	CREATE TABLE json_table_stored_data_test (
	  id SERIAL PRIMARY KEY
	, csv_row INT
	, FIRST_NAME VARCHAR
	, EMAIL_ADDRESS VARCHAR
	, WEBSITE VARCHAR
	, AVATAR VARCHAR
	, HOURS_WORKED_IN_DAY VARCHAR
	, MONEY_IN_POCKET VARCHAR
	, DAYS_SINCE_HAIRCUT INT
	, HAS_CAT BOOLEAN
	, DONT_USE VARCHAR
	, DATE_OF_BIRTH DATE
	, DATE_OF_BIRTH_UK DATE
	, LAST_LOGIN TIMESTAMP
	, BREAKFAST_TIME VARCHAR
	, DATA_DUMP VARCHAR
	, MOOD VARCHAR
	) with oids;

	ALTER TABLE json_table_stored_data_test OWNER TO postgres;
	GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE json_table_stored_data_test TO json_test;
	GRANT SELECT, UPDATE ON TABLE json_table_stored_data_test_id_seq TO json_test;
	COMMENT ON TABLE json_table_stored_data_test IS 'This table is used to store the data from the JSON table system.';
COMMIT;
