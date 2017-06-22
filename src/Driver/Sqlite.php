<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace MysqlBackup\Driver;

use Cake\Network\Exception\InternalErrorException;
use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Driver;

/**
 * Sqlite driver to export/import database backups
 */
class Sqlite extends Driver
{
    use BackupTrait;

    /**
     * Default extension for export
     * @var string
     */
    public $defaultExtension = 'sql';

    /**
     * Gets the executable command to export the database
     * @param string $filename Filename where you want to export the database
     * @return string
     * @uses $config
     * @uses getCompression()
     * @uses getValidCompressions()
     */
    protected function getExportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $executable = sprintf('%s %s .dump', $this->getBinary('sqlite3'), $this->config['database']);

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            $executable .= ' | ' . $this->getBinary($compression);
        }

        return $executable . ' > ' . $filename . ' 2>/dev/null';
    }

    /**
     * Gets the executable command to import the database
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @uses $config
     * @uses getCompression()
     * @uses getValidCompressions()
     */
    protected function getImportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $executable = sprintf('%s %s', $this->getBinary('sqlite3'), $this->config['database']);

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            $executable = sprintf('%s -dc %s | ', $this->getBinary($compression), $filename) . $executable;
        } else {
            $executable .= ' < ' . $filename;
        }

        return $executable . ' 2>/dev/null';
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses getExportExecutable()
     */
    public function export($filename)
    {
        //Executes
        exec($this->getExportExecutable($filename), $output, $returnVar);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('mysql_backup', '{0} failed with exit code `{1}`', 'sqlite3', $returnVar));
        }

        return file_exists($filename);
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses deleteAllRecords()
     * @uses getImportExecutable()
     */
    public function import($filename)
    {
        $this->deleteAllRecords();

        //Executes
        exec($this->getImportExecutable($filename), $output, $returnVar);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('mysql_backup', '{0} failed with exit code `{1}`', 'sqlite3', $returnVar));
        }

        return true;
    }
}
