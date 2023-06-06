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

use Tools\Exceptionist;

/**
 * Utility to import databases
 */
class BackupImport extends AbstractBackupUtility
{
    /**
     * Sets the filename
     * @param string $filename Filename. It can be an absolute path
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility#filename
     * @throws \ErrorException|\Tools\Exception\NotReadableException
     * @noinspection PhpMissingReturnTypeInspection
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
     * Imports the database.
     *
     * When importing, this method will trigger these events (implemented by the driver instance):
     *  - `Backup.beforeImport`: will be triggered before import;
     *  - `Backup.afterImport`: will be triggered after import.
     * @return string|false Filename path on success or `false` if the `Backup.beforeImport` event is stopped
     * @throws \ErrorException|\ReflectionException
     * @see \DatabaseBackup\Driver\AbstractDriver::afterImport()
     * @see \DatabaseBackup\Driver\AbstractDriver::beforeImport()
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupImport-utility#import
     */
    public function import()
    {
        Exceptionist::isTrue(!empty($this->filename), __d('database_backup', 'You must first set the filename'));

        //This allows the filename to be set again with a next call of this method
        $filename = $this->filename;
        unset($this->filename);

        //Dispatches the `Backup.beforeImport` event implemented by the driver
        $BeforeImport = $this->getDriver()->dispatchEvent('Backup.beforeImport');
        if ($BeforeImport->isStopped()) {
            return false;
        }

        //Imports
        $Process = $this->getProcess($this->getDriver()->getImportExecutable($filename));
        Exceptionist::isTrue($Process->isSuccessful(), __d('database_backup', 'Import failed with error message: `{0}`', rtrim($Process->getErrorOutput())));

        //Dispatches the `Backup.afterImport` event implemented by the driver
        $this->getDriver()->dispatchEvent('Backup.afterImport');

        return $filename;
    }
}
