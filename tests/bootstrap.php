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

use Cake\Cache\Cache;
use Cake\Core\Configure;

define('ROOT', dirname(__DIR__) . DS);
const CORE_PATH = ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS;
const APP = ROOT . 'tests' . DS . 'test_app' . DS;
const CONFIG = APP . 'config' . DS;
define('TMP', sys_get_temp_dir() . DS . 'cakephp-database-backup' . DS);

if (!is_readable(TMP)) {
    mkdir(TMP, 0777, true);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require_once CORE_PATH . 'config' . DS . 'bootstrap.php';

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'App',
]);

Cache::setConfig([
    '_cake_translations_' => [
        'engine' => 'File',
        'prefix' => '_cake_translations_',
        'serialize' => true,
    ],
]);

Configure::write('DatabaseBackup.target', TMP . 'backups' . DS);

require_once ROOT . 'config' . DS . 'bootstrap.php';

/**
 * Database connection settings, for each driver.
 *
 * They can be overridden before running tests by exporting the affected variable. Ex.:
 * ```
 * export db_dsn_mysql='mysql://root:root@127.0.0.1/test?encoding=utf8'; vendor/bin/phpunit
 * ```
 */
foreach (
    [
        'db_dsn_mysql' => 'mysql://test:test@mariadb/test',
        'db_dsn_pgsql' => 'postgres://postgres:postgres@postgres/test',
        'db_dsn_sqlite' => 'sqlite:///' . TMP . 'test.sq3',
    ] as $key => $value
) {
    if (!getenv($key)) {
        putenv("$key=$value");
    }
}
