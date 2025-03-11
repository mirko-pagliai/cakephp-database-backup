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
use Symfony\Component\Process\ExecutableFinder;

require_once CAKE . 'functions.php';

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

//Writes default configuration values
$defaults = [
    'DatabaseBackup.chmod' => 0664,
    'DatabaseBackup.connection' => 'default',
    'DatabaseBackup.processTimeout' => 60,
    'DatabaseBackup.target' => rtrim(ROOT, DS) . DS . 'backups',
    'DatabaseBackup.mysql.export' => '{{BINARY}} --defaults-file={{AUTH_FILE}} {{DB_NAME}}',
    'DatabaseBackup.mysql.import' => '{{BINARY}} --defaults-extra-file={{AUTH_FILE}} {{DB_NAME}}',
    'DatabaseBackup.postgres.export' => '{{BINARY}} --format=c -b --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
    'DatabaseBackup.postgres.import' => '{{BINARY}} --format=c -c -e --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
    'DatabaseBackup.sqlite.export' => '{{BINARY}} {{DB_NAME}} .dump',
    'DatabaseBackup.sqlite.import' => '{{BINARY}} {{DB_NAME}}',
];
Configure::write(array_filter($defaults, fn (string $key): bool => !Configure::check($key), ARRAY_FILTER_USE_KEY));

//@todo to be removed in 2.14.x
if (Configure::check('DatabaseBackup.mailSender')) {
    deprecationWarning('2.13.4', 'Setting the `DatabaseBackup.mailSender` value of the configuration is deprecated');
}

/**
 * It automatically discovers executables not already set by the user in the configuration.
 *
 * For `mysql` and `mysqldump` executables, it will first look for `mariadb` and `mariadb-dump` executables.
 * It then normally searches all other possible executables canonically.
 */
$ExecutableFinder = new ExecutableFinder();
foreach (['mariadb' => 'mysql', 'mariadb-dump' => 'mysqldump'] as $executable => $alias) {
    if (!Configure::check('DatabaseBackup.binaries.' . $alias)) {
        Configure::write('DatabaseBackup.binaries.' . $alias, $ExecutableFinder->find($executable));
    }
}
$executables = array_merge(['bzip2', 'gzip'], ...array_values(array_map('array_values', DATABASE_BACKUP_EXECUTABLES)));
foreach ($executables as $executable) {
    if (!Configure::check('DatabaseBackup.binaries.' . $executable)) {
        Configure::write('DatabaseBackup.binaries.' . $executable, $ExecutableFinder->find($executable));
    }
}

//Checks for the target directory
$target = Configure::read('DatabaseBackup.target');
if (!file_exists($target)) {
    mkdir($target, 0777, true);
}
if (!is_dir($target) || !is_writeable($target)) {
    trigger_error(sprintf('The directory `%s` is not writable or is not a directory', $target), E_USER_ERROR);
}

if (!function_exists('rtr')) {
    /**
     * Returns a path relative to the root path.
     *
     * @param string $path Absolute path
     * @return string Relative path
     */
    function rtr(string $path): string
    {
        if (!str_starts_with($path, ROOT)) {
            return $path;
        }

        return rtrim(substr($path, strlen(ROOT)), DS);
    }
}
