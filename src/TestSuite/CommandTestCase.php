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
 * @since       2.11.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use DatabaseBackup\Utility\BackupManager;

/**
 * Abstract class for test commands
 */
abstract class CommandTestCase extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        /**
         * @todo remove on CakePHP >= 5
         */
        if (method_exists($this, 'useCommandRunner')) {
            $this->useCommandRunner();
        }
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        BackupManager::deleteAll();

        parent::tearDown();
    }
}
