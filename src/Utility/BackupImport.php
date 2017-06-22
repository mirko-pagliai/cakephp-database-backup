<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @see         https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility
 */
namespace MysqlBackup\Utility;

use Cake\Network\Exception\InternalErrorException;
use MysqlBackup\BackupTrait;

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
     * @return \MysqlBackup\Utility\BackupImport
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility#filename
     * @throws InternalErrorException
     * @uses $driver
     * @uses $filename
     */
    public function filename($filename)
    {
        $filename = $this->getAbsolutePath($filename);

        if (!is_readable($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'File or directory `{0}` not readable', $filename));
        }

        if (!in_array($this->driver->getCompression($filename), $this->driver->getValidCompressions(), true)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid compression type'));
        }

        $this->filename = $filename;

        return $this;
    }

    /**
     * Imports the database
     * @return string Filename path
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility#import
     * @throws InternalErrorException
     * @uses $driver
     * @uses $filename
     */
    public function import()
    {
        if (empty($this->filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'You must first set the filename'));
        }

        //This allows the filename to be set again with a next call of this
        //  method
        $filename = $this->filename;
        unset($this->filename);

        $this->driver->import($filename);

        return $filename;
    }
}
