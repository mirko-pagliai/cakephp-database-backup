<?php
declare(strict_types=1);

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
use Tools\Filesystem;

/**
 * Executables. Name of driver as keys, Then, as value, an array that contains
 *  first the executable to export and then the executable to import backups.
 */
if (!defined('DATABASE_BACKUP_EXECUTABLES')) {
    define('DATABASE_BACKUP_EXECUTABLES', [
        'mysql' => ['export' => 'mysqldump', 'import' => 'mysql'],
        'postgres' => ['export' => 'pg_dump', 'import' => 'pg_restore'],
        'sqlite' => ['export' => 'sqlite3', 'import' => 'sqlite3'],
    ]);
}

/**
 * Valid extensions. Names as keys and compressions as values
 */
if (!defined('DATABASE_BACKUP_EXTENSIONS')) {
    define('DATABASE_BACKUP_EXTENSIONS', ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false]);
}

//Database connection
if (!Configure::check('DatabaseBackup.connection')) {
    Configure::write('DatabaseBackup.connection', 'default');
}

//Auto-discovers binaries
foreach (array_unique(array_merge(array_column(DATABASE_BACKUP_EXECUTABLES, 'export'), array_column(DATABASE_BACKUP_EXECUTABLES, 'import'), ['bzip2', 'gzip'])) as $binary) {
    if (!Configure::check('DatabaseBackup.binaries.' . $binary)) {
        try {
            $binaryPath = which($binary);
        } catch (\Exception $e) {
        }
        Configure::write('DatabaseBackup.binaries.' . $binary, $binaryPath ?? null);
    }
}

//Default chmod for backups. This works only on Unix
if (!Configure::check('DatabaseBackup.chmod')) {
    Configure::write('DatabaseBackup.chmod', 0664);
}

//Default executable commands to export/import databases
foreach ([
    'mysql.export' => '{{BINARY}} --defaults-file={{AUTH_FILE}} {{DB_NAME}}',
    'mysql.import' => '{{BINARY}} --defaults-extra-file={{AUTH_FILE}} {{DB_NAME}}',
    'postgres.export' => '{{BINARY}} --format=c -b --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
    'postgres.import' => '{{BINARY}} --format=c -c -e --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
    'sqlite.export' => '{{BINARY}} {{DB_NAME}} .dump',
    'sqlite.import' => '{{BINARY}} {{DB_NAME}}',
] as $k => $v) {
    if (!Configure::check('DatabaseBackup.' . $k)) {
        Configure::write('DatabaseBackup.' . $k, $v);
    }
}

//Default target directory
if (!Configure::check('DatabaseBackup.target')) {
    Configure::write('DatabaseBackup.target', Filesystem::instance()->concatenate(ROOT, 'backups'));
}

//Checks for the target directory
$target = Configure::readOrFail('DatabaseBackup.target');
if (!file_exists($target)) {
    mkdir($target, 0777);
}
if (!is_dir($target) || !is_writeable($target)) {
    trigger_error(sprintf('The directory `%s` is not writable or is not a directory', $target), E_USER_ERROR);
}
