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
 * @since       2.1.0
 */
namespace DatabaseBackup\Driver;

use Cake\Network\Exception\InternalErrorException;
use DatabaseBackup\Driver\Driver;

/**
 * Postgres driver to export/import database backups
 */
class Postgres extends Driver
{
    /**
     * Default extension for export
     * @var string
     */
    public $defaultExtension = 'sql';

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @uses getExportExecutable()
     * @throws InternalErrorException
     */
    public function export($filename)
    {
        //Executes
        exec($this->getExportExecutable($filename), $output, $returnVar);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('database_backup', '{0} failed with exit code `{1}`', 'pg_dump', $returnVar));
        }

        return file_exists($filename);
    }

    /**
     * Gets the value for the `--dbname` option for export and import
     *  executables as string. It contains the connection string with username,
     *  password and hostname.
     *
     * It returns something like:
     * <code>
     * postgresql://postgres@localhost/travis_ci_test
     * </code>
     * @return string
     * @uses $config
     */
    protected function getDbnameAsString()
    {
        $username = $this->config['username'];

        if (!empty($this->config['password'])) {
            $username .= ':' . $this->config['password'];
        }

        return sprintf('postgresql://%s@%s/%s', $username, $this->config['host'], $this->config['database']);
    }

    /**
     * Gets the executable command to export the database
     * @param string $filename Filename where you want to export the database
     * @return string
     * @uses getDbnameAsString()
     */
    protected function getExportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $executable = sprintf('%s -F c --dbname=%s', $this->getBinary('pg_dump'), $this->getDbnameAsString());

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            $executable .= ' | ' . $this->getBinary($compression);
        }

        return $executable . ' > ' . $filename . ' 2>/dev/null';
    }

    /**
     * Gets the executable command to import the database
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @uses getDbnameAsString()
     */
    protected function getImportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $executable = sprintf('%s -c --dbname=%s', $this->getBinary('pg_restore'), $this->getDbnameAsString());

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            $executable = sprintf('%s -dc %s | ', $this->getBinary($compression), $filename) . $executable;
        } else {
            $executable .= ' < ' . $filename;
        }

        return $executable;
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @uses getImportExecutable()
     * @throws InternalErrorException
     */
    public function import($filename)
    {
        //Executes
        exec($this->getImportExecutable($filename), $output, $returnVar);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('database_backup', '{0} failed with exit code `{1}`', 'pg_restore', $returnVar));
        }

        return true;
    }
}
