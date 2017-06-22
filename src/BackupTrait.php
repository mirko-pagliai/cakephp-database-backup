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
namespace MysqlBackup;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use InvalidArgumentException;
use ReflectionClass;

/**
 * A trait that provides some methods used by all other classes
 */
trait BackupTrait
{
    /**
     * Returns an absolute path
     * @param string $path Relative or absolute path
     * @return string
     * @uses getTarget()
     */
    public function getAbsolutePath($path)
    {
        if (!Folder::isAbsolute($path)) {
            return $this->getTarget() . DS . $path;
        }

        return $path;
    }

    /**
     * Gets a binary path
     * @param string $name Binary name
     * @return string
     * @since 2.0.0
     * @throws InvalidArgumentException
     */
    public function getBinary($name)
    {
        $binary = Configure::read(MYSQL_BACKUP . '.binaries.' . $name);

        if (!$binary) {
            throw new InvalidArgumentException(__d('mysql_backup', '`{0}` executable not available', $name));
        }

        return $binary;
    }

    /**
     * Gets the short name for class namespace
     * @param string $class Class namespace
     * @return string
     */
    public function getClassShortName($class)
    {
        return (new ReflectionClass($class))->getShortName();
    }

    /**
     * Gets the connection array
     * @param string|null $name Connection name
     * @return \Cake\Datasource\ConnectionInterface A connection object
     * @throws InvalidArgumentException
     */
    public function getConnection($name = null)
    {
        if (!$name) {
            $name = Configure::read(MYSQL_BACKUP . '.connection');
        }

        $connection = ConnectionManager::get($name);

        if (empty($connection)) {
            throw new InvalidArgumentException(__d('mysql_backup', 'Invalid `{0}` connection', $name));
        }

        return $connection;
    }

    /**
     * Gets the driver containing all methods to export/import database backups
     *  according to the database engine
     * @param \Cake\Datasource\ConnectionInterface|null A connection object
     * @return object A driver instance
     * @since 2.0.0
     * @uses getConnection()
     * @uses getClassShortName()
     */
    public function getDriver(ConnectionInterface $connection = null)
    {
        if (!$connection) {
            $connection = $this->getConnection();
        }

        $driver = MYSQL_BACKUP . '\\Driver\\' . $this->getClassShortName(get_class($connection->getDriver()));

        return new $driver($connection);
    }

    /**
     * Returns the target path
     * @return string
     */
    public function getTarget()
    {
        return Configure::read(MYSQL_BACKUP . '.target');
    }
}
