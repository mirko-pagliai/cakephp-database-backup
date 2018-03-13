<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility
 */
namespace DatabaseBackup\Utility;

use DatabaseBackup\BackupTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Utility to import databases
 */
class BackupImport
{
    use BackupTrait;

    /**
     * Driver containing all methods to export/import database backups
     *  according to the database engine
     * @since 2.0.0
     * @var object
     */
    protected $driver;

    /**
     * Filename where to import the database
     * @var string
     */
    protected $filename;

    /**
     * Construct
     * @uses $driver
     */
    public function __construct()
    {
        $this->driver = $this->getDriver();
    }

    /**
     * Sets the filename
     * @param string $filename Filename. It can be an absolute path
     * @return \DatabaseBackup\Utility\BackupImport
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility#filename
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @uses $filename
     */
    public function filename($filename)
    {
        $filename = $this->getAbsolutePath($filename);

        if (!is_readable($filename)) {
            throw new RuntimeException(__d('database_backup', 'File or directory `{0}` not readable', $filename));
        }

        //Checks for extension
        if (!$this->getExtension($filename)) {
            throw new InvalidArgumentException(__d('database_backup', 'Invalid file extension'));
        }

        $this->filename = $filename;

        return $this;
    }

    /**
     * Imports the database
     * @return string Filename path
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility#import
     * @throws RuntimeException
     * @uses $driver
     * @uses $filename
     */
    public function import()
    {
        if (empty($this->filename)) {
            throw new RuntimeException(__d('database_backup', 'You must first set the filename'));
        }

        //This allows the filename to be set again with a next call of this
        //  method
        $filename = $this->filename;
        unset($this->filename);

        $this->driver->import($filename);

        return $filename;
    }
}
