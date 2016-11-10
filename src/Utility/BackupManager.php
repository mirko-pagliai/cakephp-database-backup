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
use Cake\Filesystem\Folder;
use Cake\I18n\FrozenTime;
use Cake\Network\Exception\InternalErrorException;

/**
 * Utility to manage database backups.
 *
 * Please, refer to the `README.md` file to know how to use the utility and to
 * see examples.
 */
class BackupManager
{
    /**
     * Deletes a backup file
     * @param string $filename Filename
     * @return string
     * @throws InternalErrorException
     */
    public static function delete($filename)
    {
        if (!Folder::isAbsolute($filename)) {
            $filename = Configure::read('MysqlBackup.target') . DS . $filename;
        }

        if (!is_writable($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'File or directory `{0}` not writable', $filename));
        }

        return unlink($filename);
    }

    /**
     * Gets a list of database backups
     * @return array Objects of backups
     */
    public static function index()
    {
        //Gets all files
        $files = array_values((new Folder(Configure::read('MysqlBackup.target')))->read(false, false, true))[1];

        //Keeps only files with a valid extension
        $files = preg_grep('/\.sql(\.(gz|bz2))?$/', $files);

        //Parses files
        $files = array_map(function ($file) {
            return (object)[
                'filename' => basename($file),
                'extension' => extensionFromFile($file),
                'size' => filesize($file),
                'compression' => compressionFromFile($file),
                'datetime' => new FrozenTime(date('Y-m-d H:i:s', filemtime($file))),
            ];
        }, $files);

        return $files;
    }
}
