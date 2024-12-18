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
 * @since       2.0.0
 */

namespace DatabaseBackup\TestSuite;

use Cake\TestSuite\TestCase as BaseTestCase;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Utility\BackupManager;

/**
 * TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use BackupTrait;

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        BackupManager::deleteAll();
    }
}
