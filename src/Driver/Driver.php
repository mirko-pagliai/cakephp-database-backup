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

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

/**
 * Represents a driver containing all methods to export/import database backups
 *  according to the database engine
 */
abstract class Driver
{
    /**
     * Database connection
     * @var array
     */
    protected $connection;

    /**
     * Construct
     * @uses $connection
     */
    public function __construct()
    {
        $this->connection = ConnectionManager::getConfig(Configure::read(MYSQL_BACKUP . '.connection'));
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     */
    abstract public function export($filename);
}
