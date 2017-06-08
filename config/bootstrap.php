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
 * @see         https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/Configuration
 */
use Cake\Core\Configure;

//Sets the list of valid compressions
const VALID_COMPRESSIONS = [
    'Mysql' => ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false],
];

//Sets the list of valid extensions
const VALID_EXTENSIONS = [
    'Mysql' => ['sql.bz2', 'sql.gz', 'sql'],
];

//Sets the default MysqlBackup name
if (!defined('MYSQL_BACKUP')) {
    define('MYSQL_BACKUP', 'MysqlBackup');
}

//Binaries
if (!Configure::check(MYSQL_BACKUP . '.binaries')) {
    Configure::write(MYSQL_BACKUP . '.binaries', [
        'bzip2' => which('bzip2'),
        'gzip' => which('gzip'),
        'mysql' => which('mysql'),
        'mysqldump' => which('mysqldump'),
    ]);
}

//Chmod for backups
if (!Configure::check(MYSQL_BACKUP . '.chmod')) {
    Configure::write(MYSQL_BACKUP . '.chmod', 0664);
}

//Database connection
if (!Configure::check(MYSQL_BACKUP . '.connection')) {
    Configure::write(MYSQL_BACKUP . '.connection', 'default');
}

//Default target directory
if (!Configure::check(MYSQL_BACKUP . '.target')) {
    Configure::write(MYSQL_BACKUP . '.target', ROOT . DS . 'backups');
}

//Checks for the target directory
$target = Configure::read(MYSQL_BACKUP . '.target');

if (!file_exists($target)) {
    //@codingStandardsIgnoreLine
    @mkdir($target);
}

if (!is_writeable($target)) {
    trigger_error(sprintf('Directory %s not writeable', $target), E_USER_ERROR);
}
