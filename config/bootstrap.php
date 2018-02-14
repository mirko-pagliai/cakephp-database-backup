<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration
 */
use Cake\Core\Configure;

//Sets the default DatabaseBackup name
if (!defined('DATABASE_BACKUP')) {
    define('DATABASE_BACKUP', 'DatabaseBackup');
}

//Sets the redirect to `/dev/null`. This string can be concatenated to shell commands
if (!defined('REDIRECT_TO_DEV_NULL')) {
    define('REDIRECT_TO_DEV_NULL', DS == '\\' ? ' 2>nul' : ' 2>/dev/null');
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

//Redirects stderr to `/dev/null`. This suppresses the output of executed commands
if (!Configure::check(DATABASE_BACKUP . '.redirectStderrToDevNull')) {
    Configure::write(DATABASE_BACKUP . '.redirectStderrToDevNull', true);
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
