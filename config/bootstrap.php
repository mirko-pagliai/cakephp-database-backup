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
use DatabaseBackup\Executor\MysqlExecutor;

/**
 * The `Cake\Database\Driver\Mariadb` driver (CakePHP 5.5) uses the same executor as the `Mysql` one.
 * This file can be loaded more than once, but the alias can be declared only once.
 */
if (!class_exists('DatabaseBackup\Executor\MariadbExecutor', false)) {
    class_alias(MysqlExecutor::class, 'DatabaseBackup\Executor\MariadbExecutor');
}

/**
 * Since 3.1.0 the `Cake\Database\Driver\Mariadb` driver has its own commands (`DatabaseBackup.Mariadb.*`).
 * To ease the migration, if they are not set but the `DatabaseBackup.Mysql.*` ones are, the latter are used for both.
 * @todo remove this fallback in 3.2.x
 */
foreach (['export', 'import'] as $operation) {
    $mariadbKey = "DatabaseBackup.Mariadb.{$operation}";
    $mysqlKey = "DatabaseBackup.Mysql.{$operation}";

    if (!Configure::check($mariadbKey) && Configure::check($mysqlKey)) {
        Configure::write($mariadbKey, Configure::read($mysqlKey));
    }
}

$defaults = [
    'DatabaseBackup.target' => rtrim(ROOT, DS) . DS . 'backups',
    'DatabaseBackup.processTimeout' => 60,
    'DatabaseBackup.Mysql.export' => '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}"',
    'DatabaseBackup.Mysql.import' => '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" "${:DB_NAME}"',
    'DatabaseBackup.Mariadb.export' => '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}"',
    'DatabaseBackup.Mariadb.import' => '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" "${:DB_NAME}"',
    'DatabaseBackup.Postgres.export' => '"${:BINARY}" --format=c -b --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
    'DatabaseBackup.Postgres.import' => '"${:BINARY}" --format=c -c -e --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
    'DatabaseBackup.Sqlite.export' => '"${:BINARY}" "${:DB_NAME}" .dump',
    'DatabaseBackup.Sqlite.import' => '"${:BINARY}" "${:DB_NAME}"',
];

foreach ($defaults as $key => $value) {
    if (!Configure::check($key)) {
        Configure::write($key, $value);
    }
}

//Checks for the target directory
/** @var string $target */
$target = Configure::read('DatabaseBackup.target');
if (!file_exists($target)) {
    mkdir($target, 0777, true);
}
if (!is_dir($target) || !is_writeable($target)) {
    trigger_error("The directory `{$target}` is not writable or is not a directory");
}
