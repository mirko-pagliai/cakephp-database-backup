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
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Mailer\TransportFactory;

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
ini_set('intl.default_locale', 'en_US');

define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests');
define('APP', ROOT . 'tests' . DS . 'test_app' . DS);
define('APP_DIR', 'test_app');
define('WEBROOT_DIR', 'webroot');
define('WWW_ROOT', APP . 'webroot' . DS);
define('TMP', sys_get_temp_dir() . DS . 'cakephp-database-backup' . DS);
define('CONFIG', APP . 'config' . DS);
define('CACHE', TMP . 'cache' . DS);
define('LOGS', TMP . 'cakephp_log' . DS);
define('SESSIONS', TMP . 'sessions' . DS);

foreach ([
    TMP,
    LOGS,
    SESSIONS,
    CACHE . 'models',
    CACHE . 'persistent',
    CACHE . 'views',
] as $dir) {
    @mkdir($dir, 0777, true);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require_once CORE_PATH . 'config' . DS . 'bootstrap.php';

//Disables deprecation warnings for CakePHP 3.6
if (version_compare(Configure::version(), '3.6', '>=')) {
    error_reporting(E_ALL & ~E_USER_DEPRECATED);
}

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
Configure::write('DatabaseBackup.connection', 'test');
Configure::write('DatabaseBackup.target', TMP . 'backups');
Configure::write('DatabaseBackup.mailSender', 'sender@example.com');
Plugin::load('DatabaseBackup', ['bootstrap' => true, 'path' => ROOT]);
Configure::write('pluginsToLoad', ['DatabaseBackup']);

Cache::setConfig([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
]);

if (!getenv('db_dsn')) {
    putenv('db_dsn=mysql://travis@localhost/test');
}
if (!getenv('db_dsn_postgres')) {
    putenv('db_dsn_postgres=postgres://postgres@localhost/travis_ci_test');
}
if (!getenv('db_dsn_sqlite')) {
    putenv('db_dsn_sqlite=sqlite:///' . TMP . 'example.sq3');
}
ConnectionManager::setConfig('test', ['url' => getenv('db_dsn')]);
ConnectionManager::setConfig('test_postgres', ['url' => getenv('db_dsn_postgres')]);
ConnectionManager::setConfig('test_sqlite', ['url' => getenv('db_dsn_sqlite')]);
Log::setConfig('debug', [
    'className' => 'File',
    'path' => LOGS,
    'levels' => ['notice', 'info', 'debug'],
    'file' => 'debug',
]);

$transportName = 'debug';
$transportConfig = ['className' => 'Debug'];
if (class_exists(TransportFactory::class)) {
    TransportFactory::setConfig($transportName, $transportConfig);
} else {
    Email::setConfigTransport($transportName, $transportConfig);
}
Email::setConfig('default', ['transport' => $transportName, 'log' => true]);
