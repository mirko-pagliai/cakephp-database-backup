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

namespace DatabaseBackup\Test\TestCase\Utility;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\TestSuite\Fixture\SchemaLoader;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * BackupExportAndImportTest.
 *
 * This test is marked with the `#[CoversNothing]` tag because it does not have to guarantee coverage for the classes
 *  used, which is expected to be guaranteed by other tests.
 */
#[CoversNothing]
class BackupExportAndImportTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();

        //Drops the connection set by `setUpBeforeClass()`
        ConnectionManager::drop('test');
    }

    /**
     * Internal method.
     *
     * Gets all the records in a table and makes the values stringable (to simplify comparisons)
     *
     * @param \Cake\ORM\Table $Table
     * @return array<array<array-key, string>>
     */
    protected function getAllRecords(Table $Table): array
    {
        return $Table
            ->find()
            ->enableHydration(false)
            ->all()
            ->map(fn (array $record): array => array_map(
                callback: fn (mixed $value): mixed => (string)$value,
                array: $record,
            ))
            ->toArray();
    }

    #[Test]
    public function testExportAndImport(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('The `sqlite3` extension is not available');
        }

        //Sets the connection and load the schema
        ConnectionManager::setConfig('test', ['url' => 'sqlite:///' . TMP . 'test.sq3']);
        $loader = new SchemaLoader();
        /** @see /tests/schema.php */
        $loader->loadInternalFile(ROOT . 'tests' . DS . 'schema.php');

        //Sets the fixtures and fetchs tables
        $this->fixtures = ['core.Articles', 'core.Comments'];
        $this->setupFixtures();
        $Articles = $this->fetchTable('Articles');
        $Comments = $this->fetchTable('Comments');

        //Gets the initial data
        $initialArticles = $this->getAllRecords($Articles);
        $initialComments = $this->getAllRecords($Comments);

        /**
         * Exports
         */
        $BackupExport = new BackupExport();
        $BackupExport->Connection = ConnectionManager::get('test');

        $result = $BackupExport
            ->filename(TMP . 'test.sql')
            ->export();

        $this->assertSame(TMP . 'test.sql', $result);
        $this->assertFileExists($result);

        /**
         * Imports
         */
        $BackupImport = new BackupImport();
        $BackupImport->Connection = ConnectionManager::get('test');

        $result = $BackupImport
            ->filename($result)
            ->import();

        $this->assertSame(TMP . 'test.sql', $result);

        unlink($result);

        /**
         * Gets the final data.
         *
         * Asserts the initial data and the final data are equal.
         */
        $finalArticles = $this->getAllRecords($Articles);
        $finalComments = $this->getAllRecords($Comments);

        $this->assertSame($initialArticles, $finalArticles);
        $this->assertSame($initialComments, $finalComments);
    }
}
