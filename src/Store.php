<?php
namespace JsonTable;

/**
 * Store the data using the JSON table schema to determine the data structure.
 *
 * @package JSON table
 */
class Store extends Base
{
    /**
     * Load and instantiate the specified store.
     *
     * @param string $storeType The type of store to load.
     *
     * @return object The store object. Throws an exception on error.
     */
    public static function load($storeType)
    {
        self::loadAbstractStoreFile();
        self::loadStoreTypeFile($storeType);
        self::instantiateStoreClass($storeType);
    }


    /**
     * Load the abstract store file.
     *
     * @static
     *
     * @return  void
     *
     * @throws  \Exception is the abstract store file couldn't be loaded.
     */
    private static function loadAbstractStoreFile()
    {
        $abstractStoreFile = dirname(__FILE__) . "/Store/AbstractStore.php";

        if (!file_exists($abstractStoreFile) || !is_readable($abstractStoreFile)) {
            throw new \Exception("Could not load the abstract store file.");
        }

        include_once $abstractStoreFile;
    }


    /**
     * Load the store file for the specified type.
     *
     * @static
     *
     * @param   string  $storeType The type of store to load.
     *
     * @return  void
     *
     * @throws  \Exception is the store file couldn't be loaded.
     */
    private static function loadStoreTypeFile($storeType)
    {
        $storeType = ucwords($storeType);
        $storeFile = dirname(__FILE__) . "/Store/$storeType" . "Store.php";

        if (!file_exists($storeFile) || !is_readable($storeFile)) {
            throw new \Exception("Could not load the store file for $storeType.");
        }

        include_once $storeFile;
    }


    /**
     * Instantiate the store class for the specified type.
     *
     * @static
     *
     * @param   string  $storeType The type of store to load.
     *
     * @return  object  The store class instance.
     *
     * @throws  \Exception is the store class couldn't be found.
     */
    private static function instantiateStoreClass($storeType)
    {
        $storeClass = "\\JsonTable\\Store\\$storeType" . "Store";

        if (!class_exists($storeClass)) {
            throw new \Exception("Could not find the store class $storeClass");
        }

        return new $storeClass();
    }
}
