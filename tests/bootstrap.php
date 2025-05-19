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

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__) . DS);
const CORE_PATH = ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS;
const APP = ROOT . 'tests' . DS . 'test_app' . DS;
const CONFIG = APP . 'config' . DS;
define('TMP', sys_get_temp_dir() . DS . 'cakephp-database-backup' . DS);

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

putenv('db_dsn=sqlite:///' . TMP . 'test.sq3');
ConnectionManager::setConfig('test', ['url' => getenv('db_dsn')]);
ConnectionManager::alias('test', 'default');
