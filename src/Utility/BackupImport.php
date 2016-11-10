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
namespace MysqlBackup\Utility;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use Cake\Network\Exception\InternalErrorException;

/**
 * Utility to import the database
 */
class BackupImport
{
    /**
     * Compression type
     * @var bool|string
     */
    protected $compression = false;

    /**
     * Database connection
     * @var array
     */
    protected $connection;

    /**
     * Filename where to import the database
     * @var string
     */
    protected $filename;

    /**
     * Construct
     * @param string $filename Filename. It can be an absolute path
     * @uses $compression
     * @uses $connection
     * @uses $filename
     */
    public function __construct($filename)
    {
        $this->connection = ConnectionManager::config(Configure::read('MysqlBackup.connection'));

        if (!Folder::isAbsolute($filename)) {
            $filename = Configure::read('MysqlBackup.target') . DS . $filename;
        }

        if (!is_readable($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'File or directory `{0}` not readable', $filename));
        }

        //Checks for extension
        if (empty(extensionFromFile($filename))) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid file extension'));
        }
        
        $compression = compressionFromFile($filename);

        if (!in_array($compression, ['bzip2', 'gzip', false], true)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid compression type'));
        }

        $this->compression = $compression;
        $this->filename = $filename;
    }

    /**
     * Gets the executable command
     * @param bool|string $compression Compression. Supported values are
     *  `bzip2`, `gzip` and `false` (if you don't want to use compression)
     * @return string
     * @throws InternalErrorException
     */
    protected function _getExecutable($compression)
    {
        $mysql = Configure::read('MysqlBackup.bin.mysql');

        if (in_array($compression, ['bzip2', 'gzip'])) {
            $executable = Configure::read(sprintf('MysqlBackup.bin.%s', $compression));

            if (empty($executable)) {
                throw new InternalErrorException(__d('mysql_backup', '`{0}` executable not available', $compression));
            }

            return sprintf('%s -dc %%s | %s --defaults-extra-file=%%s %%s', $mysql, $executable);
        }

        //No compression
        return sprintf('cat %%s | %s --defaults-extra-file=%%s %%s', $mysql);
    }
}
