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
use Cake\Datasource\ConnectionManager;

//bzip2 binary
if (!Configure::check('MysqlBackup.bin.bzip2')) {
    Configure::write('MysqlBackup.bin.bzip2', which('bzip2'));
}

//gzip binary
if (!Configure::check('MysqlBackup.bin.gzip')) {
    Configure::write('MysqlBackup.bin.gzip', which('gzip'));
}

//mysql binary
if (!Configure::check('MysqlBackup.bin.mysql')) {
    Configure::write('MysqlBackup.bin.mysql', which('mysql'));
}

//mysqldump binary
if (!Configure::check('MysqlBackup.bin.mysqldump')) {
    Configure::write('MysqlBackup.bin.mysqldump', which('mysqldump'));
}

//Chmod for backups
if (!Configure::check('MysqlBackup.chmod')) {
    Configure::write('MysqlBackup.chmod', 0664);
}

//Database connection
if (!Configure::check('MysqlBackup.connection')) {
    Configure::write('MysqlBackup.connection', 'default');
}

//Default backups directory
if (!Configure::check('MysqlBackup.target')) {
    Configure::write('MysqlBackup.target', ROOT . DS . 'backups');
}

//Checks for mysql binary
if (empty(Configure::read('MysqlBackup.bin.mysql'))) {
    trigger_error(sprintf('The `%s` binary was not found', 'mysql'), E_USER_ERROR);
}

//Checks for mysqldump binary
if (empty(Configure::read('MysqlBackup.bin.mysqldump'))) {
    trigger_error(sprintf('The `%s` binary was not found', 'mysqldump'), E_USER_ERROR);
}

//Checks for connection
$connection = Configure::read('MysqlBackup.connection');

if (empty(ConnectionManager::config($connection))) {
    trigger_error(sprintf('Invalid `%s` connection', $connection), E_USER_ERROR);
}

if (!is_writeable(Configure::read('MysqlBackup.target'))) {
    trigger_error(sprintf('Directory %s not writeable', Configure::read('MysqlBackup.target')), E_USER_ERROR);
}
