<?php
/** @noinspection PhpUnhandledExceptionInspection */
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

use Cake\ORM\Table;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;

class BackupExportAndImportTest extends TestCase
{
    /**
     * @var \Cake\ORM\Table
     */
    protected Table $Articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $Comments;

    /**
     * @var array<string>
     */
    public $fixtures = ['core.Articles', 'core.Comments'];

    /**
     * Internal method to get all records from the database
     * @return array<string, array>
     */
    protected function getAllRecords(): array
    {
        foreach (['Articles', 'Comments'] as $name) {
            $records[$name] = $this->{$name}->find()->enableHydration(false)->toArray();
        }

        return $records;
    }

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var \Cake\Database\Connection $connection */
        $connection = $this->getConnection('test');
        foreach (['Articles', 'Comments'] as $name) {
            $this->{$name} ??= $this->getTable($name, compact('connection'));
        }
    }

    /**
     * Test for `export()` and `import()` methods. It tests that the backup is properly exported and then imported
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testExportAndImport(): void
    {
        $BackupExport = new BackupExport();
        $BackupImport = new BackupImport();

        foreach (array_keys(DATABASE_BACKUP_EXTENSIONS) as $extension) {
            $expectedFilename = $this->getAbsolutePath(uniqid('example_') . '.' . $extension);

            //Initial records. 3 articles and 6 comments
            $initial = $this->getAllRecords();
            $this->assertCount(3, $initial['Articles']);
            $this->assertCount(6, $initial['Comments']);

            //Exports backup and deletes article with ID 2 and comment with ID 4
            $result = $BackupExport->filename($expectedFilename)->export();
            $this->assertSame($expectedFilename, $result);
            $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
            $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

            //Records after delete. 2 articles and 5 comments
            $afterDelete = $this->getAllRecords();
            $this->assertCount(count($initial['Articles']) - 1, $afterDelete['Articles']);
            $this->assertCount(count($initial['Comments']) - 1, $afterDelete['Comments']);

            //Imports backup. Now initial records are the same of final records
            $result = $BackupImport->filename($expectedFilename)->import();
            $this->assertSame($expectedFilename, $result);
            $final = $this->getAllRecords();
            $this->assertEquals($initial, $final);

            //Gets the difference (`$diff`) between records after delete and records after import (`$final`)
            $diff = $final;
            foreach ($final as $model => $finalValues) {
                foreach ($finalValues as $finalKey => $finalValue) {
                    foreach ($afterDelete[$model] as $deletedValue) {
                        if ($finalValue == $deletedValue) {
                            unset($diff[$model][$finalKey]);
                        }
                    }
                }
            }
            $this->assertCount(1, $diff['Articles']);
            $this->assertCount(1, $diff['Comments']);

            //Difference is article with ID 2 and comment with ID 4
            $this->assertSame([2], array_column($diff['Articles'], 'id'));
            $this->assertSame([4], array_column($diff['Comments'], 'id'));
        }
    }
}
