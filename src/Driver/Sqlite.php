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
 * @since       2.0.0
 */
namespace DatabaseBackup\Driver;

use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;

/**
 * Sqlite driver to export/import database backups
 */
class Sqlite extends Driver
{
    use BackupTrait;

    /**
     * Gets the executable command to export the database
     * @return string
     * @uses $config
     */
    protected function _exportExecutable()
    {
        return sprintf('%s %s .dump', $this->getBinary('sqlite3'), $this->config['database']);
    }

    /**
     * Gets the executable command to import the database
     * @return string
     * @uses $config
     */
    protected function _importExecutable()
    {
        return sprintf('%s %s', $this->getBinary('sqlite3'), $this->config['database']);
    }

    /**
     * Called before import
     * @return void
     * @uses truncateTables()
     */
    public function beforeImport()
    {
        $this->truncateTables();
    }
}
