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
use Cake\TestSuite\TestCase as CakeTestCase;
use Tools\ReflectionTrait;
use Tools\TestSuite\TestCaseTrait;

/**
 * TestCase class
 */
abstract class TestCase extends CakeTestCase
{
    use ReflectionTrait;
    use TestCaseTrait;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('DatabaseBackup.connection', 'test');
        $this->loadPlugins(['DatabaseBackup']);
    }

    /**
     * Called after every test method
     * @return void
     * @uses deleteAllBackups()
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->deleteAllBackups();
    }

    /**
     * Internal method to create a backup file
     * @return string
     */
    protected function createBackup()
    {
        return $this->BackupExport->filename('backup.sql')->export();
    }

    /**
     * Internal method to creates some backup files
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function createSomeBackups($sleep = false)
    {
        $files[] = $this->BackupExport->filename('backup.sql')->export();

        $sleep ? sleep(1) : null;
        $files[] = $this->BackupExport->filename('backup.sql.bz2')->export();

        $sleep ? sleep(1) : null;
        $files[] = $this->BackupExport->filename('backup.sql.gz')->export();

        return $files;
    }

    /**
     * Internal method to deletes all backups
     * @return void
     */
    public function deleteAllBackups()
    {
        safe_unlink_recursive(Configure::read('DatabaseBackup.target'));
    }
}
