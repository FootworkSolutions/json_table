<?php
namespace JsonTable\Validate;

/**
 * Foreign key validator interface.
 *
 * @package JSON table
 */
interface InterfaceForeignKeyValidator
{
	/**
	 * Check that the foreign key hash matches the specified resource.
	 *
	 * @access public
	 *
	 * @param string $ps_row_hash The hash of data from the CSV row to be validated.
	 * @param string $ps_reference_resource The reference resource.
	 * @param array $pa_reference_fields The reference fields.
	 *
	 * @return boolean Is the data valid.
	 */
	public function validate($ps_row_hash, $ps_reference_resource, array $pa_reference_fields);
}
