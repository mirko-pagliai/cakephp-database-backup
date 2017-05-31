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
 * @see         https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility
 */
namespace MysqlBackup\Utility;

use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\I18n\FrozenTime;
use Cake\Network\Exception\InternalErrorException;

/**
 * Utility to manage database backups
 */
class BackupManager
{
    /**
     * Deletes a backup file
     * @param string $filename Filename
     * @return bool
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility#delete
     * @throws InternalErrorException
     */
    public static function delete($filename)
    {
        if (!Folder::isAbsolute($filename)) {
            $filename = Configure::read(MYSQL_BACKUP . '.target') . DS . $filename;
        }

        if (!is_writable($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'File or directory `{0}` not writable', $filename));
        }

        return unlink($filename);
    }

    /**
     * Deletes all backup files
     * @return array List of deleted backup files
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility#deleteAll
     * @since 1.0.1
     * @uses delete()
     * @uses index()
     */
    public static function deleteAll()
    {
        $deleted = [];

        foreach (self::index() as $file) {
            self::delete($file->filename);

            $deleted[] = $file->filename;
        }

        return $deleted;
    }

    /**
     * Returns a list of database backups
     * @return array Objects of backups
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility#index
     */
    public static function index()
    {
        //Gets all files
        $files = array_values((new Folder(Configure::read(MYSQL_BACKUP . '.target')))->read(false, false, true))[1];

        //Keeps only files with a valid extension
        $files = preg_grep('/\.sql(\.(gz|bz2))?$/', $files);

        //Parses files
        $files = array_map(function ($file) {
            return (object)[
                'filename' => basename($file),
                'extension' => extensionFromFile($file),
                'compression' => compressionFromFile($file),
                'size' => filesize($file),
                'datetime' => new FrozenTime(date('Y-m-d H:i:s', filemtime($file))),
            ];
        }, $files);

        //Re-orders, using the datetime value
        usort($files, function ($a, $b) {
            return $b->datetime >= $a->datetime;
        });

        return $files;
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older.
     * @param int $rotate Number of backups that you want to keep
     * @return array Array of deleted files
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupManager-utility#rotate
     * @throws InternalErrorException
     * @uses delete()
     * @uses index()
     */
    public static function rotate($rotate)
    {
        if (!isPositive($rotate)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid rotate value'));
        }

        //Gets all files
        $files = self::index();

        //Returns, if the number of files to keep is larger than the number of
        //  files that are present
        if ($rotate >= count($files)) {
            return [];
        }

        //The number of files to be deleted is equal to the number of files
        //  that are present less the number of files that you want to keep
        $diff = count($files) - $rotate;

        //Files that will be deleted
        $files = array_map(function ($file) {
            return $file;
        }, array_slice($files, -$diff, $diff));

        //Deletes
        foreach ($files as $file) {
            self::delete($file->filename);
        }

        return $files;
    }
}
