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
namespace DatabaseBackup\TestSuite;

use Cake\Core\Configure;
use Cake\TestSuite\ConsoleIntegrationTestCase as BaseConsoleIntegrationTestCase;
use DatabaseBackup\TestSuite\TestTrait;
use DatabaseBackup\Utility\BackupManager;
use Tools\Filesystem;

/**
 * A test case class intended to make integration tests of cake console commands
 * easier
 */
abstract class ConsoleIntegrationTestCase extends BaseConsoleIntegrationTestCase
{
    use TestTrait;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        if (method_exists($this, 'loadPlugins')) {
            $this->loadPlugins(Configure::read('pluginsToLoad') ?: ['MeTools']);
        }
    }

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        if (LOGS !== TMP) {
            Filesystem::instance()->unlinkRecursive(LOGS, ['.gitkeep', 'empty'], true);
        }

        //Deletes all backup files
        BackupManager::deleteAll();

        parent::tearDown();
    }
}
