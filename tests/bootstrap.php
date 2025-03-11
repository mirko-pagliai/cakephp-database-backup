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
use Cake\Datasource\ConnectionManager;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\TestSuite\TestEmailTransport;
use DatabaseBackup\Utility\BackupManager;

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
ini_set('intl.default_locale', 'en_US');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__) . DS);
const CORE_PATH = ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS;
const CAKE = CORE_PATH . 'src' . DS;
const APP = ROOT . 'tests' . DS . 'test_app' . DS;
const APP_DIR = 'test_app' . DS;
const WWW_ROOT = APP . 'webroot' . DS;
define('TMP', sys_get_temp_dir() . DS . 'cakephp-database-backup' . DS);
const CONFIG = APP . 'config' . DS;
const CACHE = TMP . 'cache' . DS;
const LOGS = TMP . 'cakephp_log' . DS;
const SESSIONS = TMP . 'sessions' . DS;

$dirs = [TMP, LOGS, SESSIONS, CACHE . 'models', CACHE . 'persistent', CACHE . 'views'];
foreach (array_filter($dirs, fn (string $dir): bool => !file_exists($dir)) as $dir) {
    mkdir($dir, 0777, true);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require_once CORE_PATH . 'config' . DS . 'bootstrap.php';

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'App',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => APP_DIR,
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => ['plugins' => [APP . 'Plugin' . DS]],
]);

/** @todo to be removed with CakePHP >= 5.1 */
$translationsName = version_compare(Configure::version(), '5.1', '>=') ? '_cake_translations_' : '_cake_core_';
Cache::setConfig([
    $translationsName => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
]);

TransportFactory::setConfig('debug', ['className' => TestEmailTransport::class]);
Mailer::setConfig('default', ['transport' => 'debug']);

if (!getenv('db_dsn')) {
    putenv('db_dsn=mysql://travis@localhost/test');

    $driverTest = getenv('driver_test');
    if ($driverTest && $driverTest != 'mysql') {
        if ($driverTest == 'sqlite') {
            putenv('db_dsn=sqlite:///' . TMP . 'test.sq3');
        } elseif ($driverTest == 'postgres') {
            putenv('db_dsn=postgres://postgres@localhost/test');
        }
    }
}
ConnectionManager::setConfig('test', ['url' => getenv('db_dsn')]);

Configure::write('DatabaseBackup.connection', 'test');
Configure::write('DatabaseBackup.target', TMP . 'backups' . DS);

/**
 * For Xampp
 */
if (!defined('IS_WIN')) {
    define('IS_WIN', DIRECTORY_SEPARATOR === '\\');
}
if (IS_WIN && file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')) {
    Configure::write('DatabaseBackup.binaries.mysql', 'C:\\xampp\\mysql\\bin\\mysql.exe');
    Configure::write('DatabaseBackup.binaries.mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe');
}

require_once ROOT . 'config' . DS . 'bootstrap.php';

$loader = new SchemaLoader();
/** @uses tests/schema.php */
$loader->loadInternalFile(ROOT . 'tests' . DS . 'schema.php');

echo 'Running tests for `' . (new BackupManager())->getDriverName() . '` driver ' . PHP_EOL;
