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

/**
 * Backward compatibility for old configuration names, such as `DatabaseBackup.mysql.export`.
 */
foreach (array_keys(DATABASE_BACKUP_EXECUTABLES) as $driverKey) {
    foreach (['export', 'import'] as $operationKey) {
        $name = 'DatabaseBackup.' . $driverKey . '.' . $operationKey;
        if (!Configure::check($name)) {
            continue;
        }
        $expectedName = 'DatabaseBackup.' . ucfirst($driverKey) . '.' . $operationKey;
        Configure::write($expectedName, Configure::consume($name));

        deprecationWarning('2.14.2', sprintf(
            'The configuration name `%s` is deprecated and will be removed in a future release. Please use `%s` instead.',
            $name,
            $expectedName
        ));
    }
}

if (Configure::check('DatabaseBackup.connection')) {
    deprecationWarning('2.14.2', sprintf(
        'The configuration name `%s` is deprecated and will be removed in a future release. If you need to use a connection other than `default`, use the `$Connection` argument to the `BackupExport`/`BackupImport` constructor when instantiating these classes.',
        'DatabaseBackup.connection'
    ));
}

//Writes default configuration values
$defaults = [
    'DatabaseBackup.chmod' => 0664,
    'DatabaseBackup.processTimeout' => 60,
    'DatabaseBackup.target' => rtrim(ROOT, DS) . DS . 'backups',
    'DatabaseBackup.Mysql.export' => '{{BINARY}} --defaults-file={{AUTH_FILE}} {{DB_NAME}}',
    'DatabaseBackup.Mysql.import' => '{{BINARY}} --defaults-extra-file={{AUTH_FILE}} {{DB_NAME}}',
    'DatabaseBackup.Postgres.export' => '{{BINARY}} --format=c -b --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
    'DatabaseBackup.Postgres.import' => '{{BINARY}} --format=c -c -e --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
    'DatabaseBackup.Sqlite.export' => '{{BINARY}} {{DB_NAME}} .dump',
    'DatabaseBackup.Sqlite.import' => '{{BINARY}} {{DB_NAME}}',
];
Configure::write(array_filter($defaults, fn (string $key): bool => !Configure::check($key), ARRAY_FILTER_USE_KEY));

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

/**
 * Aliases for old `Driver` classes.
 *
 * @todo to be removed in version 2.15.0
 */
foreach (
    [
        'DatabaseBackup\Executor\AbstractExecutor' => 'DatabaseBackup\Driver\AbstractDriver',
        'DatabaseBackup\Executor\MysqlExecutor' => 'DatabaseBackup\Driver\Mysql',
        'DatabaseBackup\Executor\PostgresExecutor' => 'DatabaseBackup\Driver\Postgres',
        'DatabaseBackup\Executor\SqliteExecutor' => 'DatabaseBackup\Driver\Sqlite',
    ] as $class => $alias
) {
    if (!class_exists($alias)) {
        class_alias(class: $class, alias: $alias);
    }
}
