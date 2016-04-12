<?php
namespace JsonTable\Analyse;

/**
 * Foreign key validator interface.
 *
 * @package JSON table
 */
interface AnalyseInterface
{
    /**
     * Validate that the file is valid.
     *
     * @access  public
     *
     * @return  boolean Whether the file is valid.
     */
    public function validate();
}
