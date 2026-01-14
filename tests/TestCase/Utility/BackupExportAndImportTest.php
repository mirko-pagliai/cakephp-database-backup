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

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\TestSuite\Fixture\SchemaLoader;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\ExecutableFinder;

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
                callback: fn (mixed $value): string => (string)$value,
                array: $record,
            ))
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        //If some binaries are missing, sets the old aliases
        $ExecutableFinder = new ExecutableFinder();
        foreach (['mariadb-dump' => 'mysqldump', 'mariadb' => 'mysql'] as $binary => $oldAlias) {
            if (!$ExecutableFinder->find($binary)) {
                Configure::write("DatabaseBackup.binaries.$binary", $ExecutableFinder->find($oldAlias));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        //Deletes any custom and previously set binaries by `setUpBeforeClass()`
        Configure::delete('DatabaseBackup.binaries');

        //Removes sqlite database
        if (file_exists(TMP . 'test.sq3')) {
            unlink(TMP . 'test.sq3');
        }
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (glob(Configure::read('DatabaseBackup.target') . '*') as $file) {
            unlink($file);
        }
    }

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
     * @param string $extension
     * @param class-string $expectedExecutor
     * @return void
     */
    #[Test]
    #[TestWith(['mysql', MysqlExecutor::class])]
    #[TestWith(['pgsql', PostgresExecutor::class])]
    #[TestWith(['sqlite', SqliteExecutor::class])]
    public function testExportAndImport(string $extension, string $expectedExecutor): void
    {
        if (!extension_loaded('pdo_' . $extension)) {
            $this->fail('The `pdo_' . $extension . '` extension is not available');
        }

        /**
         * Sets the connection and load the schema.
         *
         * The settings for database connections are defined in the bootstrap file and may have been overridden before
         *  testing by exporting the affected variable.
         *
         * @see tests/bootstrap.php
         */
        ConnectionManager::setConfig('test', ['url' => getenv('db_dsn_' . $extension)]);
        $loader = new SchemaLoader();
        /** @see /tests/schema.php */
        $loader->loadInternalFile(ROOT . 'tests' . DS . 'schema.php');

        //Sets the fixtures and fetches tables
        $this->fixtures = ['core.Articles', 'core.Comments'];
        $this->setupFixtures();
        $ArticlesTable = $this->fetchTable('Articles');
        $CommentsTable = $this->fetchTable('Comments');

        //Gets the initial data
        $initialArticles = $this->getAllRecords($ArticlesTable);
        $initialComments = $this->getAllRecords($CommentsTable);

        /**
         * Exports
         */
        $BackupExport = new BackupExport(Connection: ConnectionManager::get('test'));

        $result = $BackupExport
            ->filename('test.sql')
            ->export();

        $this->assertInstanceOf($expectedExecutor, $BackupExport->Executor);
        $this->assertSame(Configure::read('DatabaseBackup.target') . 'test.sql', $result);
        $this->assertFileExists($result);

        /**
         * Deletes all records and asserts both tables are now empty.
         *
         * This allows us to exclude a possible false positive.
         */
        $ArticlesTable->deleteAll(conditions: ['id >=' => 1]);
        $CommentsTable->deleteAll(conditions: ['id >=' => 1]);
        $this->assertEmpty($this->getAllRecords($ArticlesTable));
        $this->assertEmpty($this->getAllRecords($CommentsTable));

        /**
         * Imports
         */
        $BackupImport = new BackupImport(Connection: ConnectionManager::get('test'));

        $result = $BackupImport
            ->filename($result)
            ->import();

        $this->assertInstanceOf($expectedExecutor, $BackupImport->Executor);
        $this->assertSame(Configure::read('DatabaseBackup.target') . 'test.sql', $result);

        /**
         * Gets the final data.
         *
         * Asserts the initial data and the final data are equal.
         */
        $finalArticles = $this->getAllRecords($ArticlesTable);
        $finalComments = $this->getAllRecords($CommentsTable);

        $this->assertSame($initialArticles, $finalArticles);
        $this->assertSame($initialComments, $finalComments);
    }
}
