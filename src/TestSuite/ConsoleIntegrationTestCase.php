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
 * @since       2.2.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\TestSuite\ConsoleIntegrationTestCase as CakeConsoleIntegrationTestCase;
use DatabaseBackup\TestSuite\TestCaseTrait;
use DatabaseBackup\Utility\BackupExport;

/**
 * ConsoleIntegrationTestCase class
 */
class ConsoleIntegrationTestCase extends CakeConsoleIntegrationTestCase
{
    use TestCaseTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->BackupExport = new BackupExport;

        $this->deleteAllBackups();
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     * @uses deleteAllBackups()
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->deleteAllBackups();

        Configure::write(DATABASE_BACKUP . '.connection', 'test');
    }

    /**
     * Asserts shell exited with the error code
     * @param string $message Failure message to be appended to the generated
     *  message
     * @return void
     */
    public function assertExitWithError($message = '')
    {
        $this->assertExitCode(Shell::CODE_ERROR, $message);
    }

    /**
     * Asserts shell exited with the success code
     * @param string $message Failure message to be appended to the generated
     *  message
     * @return void
     */
    public function assertExitWithSuccess($message = '')
    {
        $this->assertExitCode(Shell::CODE_SUCCESS, $message);
    }
}
