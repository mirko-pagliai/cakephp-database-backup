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

//Writes default configuration values
$defaults = [
    'DatabaseBackup.chmod' => 0664,
    'DatabaseBackup.connection' => 'default',
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

//Checks for the target directory
$target = Configure::read('DatabaseBackup.target');
if (!file_exists($target)) {
    mkdir($target, 0777, true);
}
if (!is_dir($target) || !is_writeable($target)) {
    trigger_error(sprintf('The directory `%s` is not writable or is not a directory', $target), E_USER_ERROR);
}
