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

use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use DatabaseBackup\Compression;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * BackupExportAndImportTest class.
 *
 * Performs tests common to the `BackupExport` and `BackupImport` classes.
 */
#[CoversClass(BackupExport::class)]
#[CoversClass(BackupImport::class)]
class BackupExportAndImportTest extends TestCase
{
    /**
     * @var \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>>
     */
    protected Table $Articles;

    /**
     * @var \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>>
     */
    protected Table $Comments;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * Internal method to get all records from the database.
     *
     * @return non-empty-array<'Articles'|'Comments', mixed>
     */
    protected function getAllRecords(): array
    {
        foreach (['Articles', 'Comments'] as $name) {
            $records[$name] = $this->{$name}->find()->enableHydration(false)->all()->toArray();
        }

        return $records;
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Articles', 'Comments'] as $name) {
            $this->{$name} ??= $this->fetchTable($name);
        }
    }

    /**
     * Test for `BackupExport::export()` and `BackupImport::import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     */
    #[Test]
    #[TestWith([Compression::None])]
    #[TestWith([Compression::Gzip])]
    #[TestWith([Compression::Bzip2])]
    public function testExportAndImport(Compression $Compression): void
    {
        $BackupExport = new BackupExport();
        $BackupImport = new BackupImport();

        $expectedFilename = TMP . 'backups' . DS . 'backup_' . uniqid('example_') . '.' . $Compression->value;

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

        unlink($expectedFilename);
    }
}
