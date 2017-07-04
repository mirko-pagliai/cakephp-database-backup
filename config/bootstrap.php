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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration
 */
use Cake\Core\Configure;

//Sets the default DatabaseBackup name
if (!defined('DATABASE_BACKUP')) {
    define('DATABASE_BACKUP', 'DatabaseBackup');
}

//Sets the list of valid compressions
const VALID_COMPRESSIONS = ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip'];

//Sets the list of valid extensions
const VALID_EXTENSIONS = ['sql.bz2', 'sql.gz', 'sql'];

//Binaries
foreach (['bzip2', 'gzip', 'mysql', 'mysqldump', 'pg_dump', 'pg_restore', 'sqlite3'] as $binary) {
    if (!Configure::check(DATABASE_BACKUP . '.binaries.' . $binary)) {
        Configure::write(DATABASE_BACKUP . '.binaries.' . $binary, which($binary));
    }
}

//Chmod for backups
if (!Configure::check(DATABASE_BACKUP . '.chmod')) {
    Configure::write(DATABASE_BACKUP . '.chmod', 0664);
}

//Database connection
if (!Configure::check(DATABASE_BACKUP . '.connection')) {
    Configure::write(DATABASE_BACKUP . '.connection', 'default');
}

//Default target directory
if (!Configure::check(DATABASE_BACKUP . '.target')) {
    Configure::write(DATABASE_BACKUP . '.target', ROOT . DS . 'backups');
}

//Checks for the target directory
$target = Configure::read(DATABASE_BACKUP . '.target');

if (!file_exists($target)) {
    //@codingStandardsIgnoreLine
    @mkdir($target);
}

if (!is_writeable($target)) {
    trigger_error(sprintf('Directory %s not writeable', $target), E_USER_ERROR);
}
