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
 * @since       2.0.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase as BaseTestCase;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\TestSuite\TestTrait;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use Tools\Filesystem;
use Tools\TestSuite\BackwardCompatibilityTrait;
use Tools\TestSuite\ReflectionTrait;

/**
 * TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use BackupTrait;
    use BackwardCompatibilityTrait;
    use ReflectionTrait;
    use TestTrait;

    /**
     * `BackupManager` instance
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

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

        $this->BackupExport = $this->BackupExport ?: new BackupExport();
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

    /**
     * Internal method to mock a driver
     * @param class-string<object> $className Driver class name
     * @param array $methods The list of methods to mock
     * @return \DatabaseBackup\Driver\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForDriver($className, array $methods)
    {
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getConnection('test')])
            ->getMock();
    }
}
