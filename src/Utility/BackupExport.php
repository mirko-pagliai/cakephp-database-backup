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
 * Utility to export the database.
 *
 * Please, refer to the `README` file to know how to use the utility and to
 * see examples.
 */
class BackupExport
{
    /**
     * Compression type
     * @var bool|string
     */
    protected $compression;

    /**
     * Database connection
     * @var array
     */
    protected $connection;

    /**
     * Executable command
     * @var string
     */
    protected $executable;

    /**
     * Rotate limit. This is the number of backups you want to keep. So, it
     *  will delete all backups that are older.
     * @var int
     */
    protected $rotate;

    /**
     * Construct
     * @uses filename()
     * @uses $connection
     */
    public function __construct()
    {
        $this->connection = ConnectionManager::config(Configure::read('MysqlBackup.connection'));

        //This will set filename, compression and executable
        $this->filename('backup_{$DATABASE}_{$DATETIME}.sql');
    }

    /**
     * Sets the compression
     *
     * Using this method, the executable type will be automatically setted.
     * @param bool|string $compression Compression or `false` to disable
     * @return \MysqlBackup\Utility\BackupExport
     * @throws InternalErrorException
     * @uses $compression
     * @uses $executable
     */
    public function compression($compression)
    {
        $mysqldump = Configure::read('MysqlBackup.bin.mysqldump');

        if ($compression === 'bzip2') {
            $bzip2 = Configure::read('MysqlBackup.bin.bzip2');
            $this->executable = sprintf('%s --defaults-file=%%s %%s | %s > %%s', $mysqldump, $bzip2);
        } elseif ($compression === 'gzip') {
            $gzip = Configure::read('MysqlBackup.bin.gzip');
            $this->executable = sprintf('%s --defaults-file=%%s %%s | %s > %%s', $mysqldump, $gzip);
        } elseif ($compression === false) {
            $this->executable = sprintf('%s --defaults-file=%%s %%s > %%s', $mysqldump);
        } else {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid compression type'));
        }

        $this->compression = $compression;

        return $this;
    }

    /**
     * Sets the filename.
     *
     * Using this method, the compression type will be automatically setted
     * by the filename.
     * @param string $filename Filename. It can be an absolute path and may
     *  contain patterns
     * @return \MysqlBackup\Utility\BackupExport
     * @throws InternalErrorException
     * @uses compression()
     * @uses $connection
     * @uses $filename
     */
    public function filename($filename)
    {
        //Replaces patterns
        $filename = str_replace(
            ['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'],
            [$this->connection['database'], date('YmdHis'), $this->connection['host'], time()],
            $filename
        );

        if (!Folder::isAbsolute($filename)) {
            $filename = Configure::read('MysqlBackup.target') . DS . $filename;
        }

        $this->filename = $filename;

        //Checks for extension
        if (!preg_match('/\.(sql(\.(gz|bz2))?)$/', $filename, $matches)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid file extension'));
        }

        //Sets the compression
        $this->compression(['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false][$matches[1]]);

        return $this;
    }

    /**
     * Sets the number of backups you want to keep. So, it will delete all
     * backups that are older
     * @param int $rotate Number of backups you want to keep
     * @return \MysqlBackup\Utility\BackupExport
     * @throws InternalErrorException
     * @uses $rotate
     */
    public function rotate($rotate)
    {
        if (!isPositive($rotate)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid rotate value'));
        }

        $this->rotate = $rotate;

        return $this;
    }

    /**
     * Stores the authentication data in a temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @uses $connection
     * @return string File path
     */
    protected function _storeAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->connection['username'],
            empty($this->connection['password']) ? null : $this->connection['password'],
            $this->connection['host']
        ));

        return $auth;
    }

    /**
     * Exports the database
     * @return string Filename path
     * @uses _storeAuth()
     * @uses $connection
     * @uses $executable
     * @uses $filename
     */
    public function export()
    {
        //Stores the authentication data in a temporary file
        $auth = $this->_storeAuth();

        $this->executable = sprintf(
            $this->executable,
            $auth,
            $this->connection['database'],
            $this->filename
        );

        unlink($auth);

        return $this->filename;
    }
}
