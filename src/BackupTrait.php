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
use Cake\Filesystem\Folder;

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
     * Returns the compression starting from a filename
     * @param string $filename Filename
     * @return string|bool|null Compression type or `null` on failure. `false`
     *  means no compression (a simple sql file)
     * @uses getExtension()
     */
    public function getCompression($filename)
    {
        //Gets the extension
        $extension = $this->getExtension($filename);

        $compressions = ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false];

        if (!array_key_exists($extension, $compressions)) {
            return null;
        }

        return $compressions[$extension];
    }

    /**
     * Returns the extension starting from a compression type or a filename
     * @param string $compressionOrFilename Compression type or filename
     * @return string|null Extension or `null` on failure
     */
    public function getExtension($compressionOrFilename)
    {
        $compressions = ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false];

        $extension = array_search($compressionOrFilename, $compressions, true);

        if ($extension) {
            return $extension;
        }

        if (preg_match('/\.(sql(\.(gz|bz2))?)$/', $compressionOrFilename, $matches)) {
            return $matches[1];
        }

        return null;
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
