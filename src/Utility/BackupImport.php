<?php
declare(strict_types=1);

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
use Tools\Exceptionist;

/**
 * Utility to import databases
 */
class BackupImport
{
    use BackupTrait;

    /**
     * Driver containing all methods to export/import database backups according to the connection
     * @var \DatabaseBackup\Driver\Driver
     */
    protected $Driver;

    /**
     * Filename where to import the database
     * @var string
     */
    protected $filename;

    /**
     * Construct
     * @throws \ErrorException|\ReflectionException
     */
    public function __construct()
    {
        $this->Driver = $this->getDriver();
    }

    /**
     * Sets the filename
     * @param string $filename Filename. It can be an absolute path
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility#filename
     * @throws \ErrorException
     * @throws \Tools\Exception\NotReadableException
     */
    public function filename(string $filename)
    {
        $filename = Exceptionist::isReadable($this->getAbsolutePath($filename));

        //Checks for extension
        Exceptionist::isTrue($this->getExtension($filename), __d('database_backup', 'Invalid file extension'));

        $this->filename = $filename;

        return $this;
    }

    /**
     * Imports the database
     * @return string Filename path
     * @throws \ErrorException|\ReflectionException
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility#import
     */
    public function import(): string
    {
        Exceptionist::isTrue(!empty($this->filename), __d('database_backup', 'You must first set the filename'));

        //This allows the filename to be set again with a next call of this method
        $filename = $this->filename;
        unset($this->filename);

        $this->Driver->import($filename);

        return $filename;
    }
}
