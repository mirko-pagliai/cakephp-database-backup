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

use Cake\Core\Configure;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Utility\BackupExport;
use MeTools\TestSuite\TestCase as BaseTestCase;

/**
 * TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use BackupTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = $this->BackupExport ?: new BackupExport();
    }

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        //Deletes all backup files
        @unlink_recursive(Configure::read('DatabaseBackup.target'));
    }

    /**
     * Internal method to create a backup file
     * @param string $filename Filename
     * @return string
     */
    protected function createBackup(string $filename = 'backup.sql'): string
    {
        return $this->BackupExport->filename($filename)->export();
    }

    /**
     * Internal method to creates some backup files
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     * @uses createBackup()
     */
    protected function createSomeBackups(bool $sleep = false): array
    {
        $files[] = $this->createBackup();

        $sleep ? sleep(1) : null;
        $files[] = $this->createBackup('backup.sql.bz2');

        $sleep ? sleep(1) : null;
        $files[] = $this->createBackup('backup.sql.gz');

        return $files;
    }
}
