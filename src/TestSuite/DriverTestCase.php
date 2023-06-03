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

use Cake\Event\EventList;
use Cake\ORM\Table;
use DatabaseBackup\Driver\Driver;
use Tools\TestSuite\ReflectionTrait;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $Articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $Comments;

    /**
     * @var \DatabaseBackup\Driver\Driver
     */
    protected Driver $Driver;

    /**
     * Driver class
     * @since 2.5.1
     * @var class-string<\DatabaseBackup\Driver\Driver>
     */
    protected string $DriverClass;

    /**
     * Name of the database connection
     * @var string
     */
    protected string $connection;

    /**
     * @var array<string>
     */
    public $fixtures = ['core.Articles', 'core.Comments'];

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

        if (empty($this->DriverClass) || empty($this->Driver)) {
            /** @var class-string<\DatabaseBackup\Driver\Driver> $DriverClass */
            $DriverClass = 'DatabaseBackup\\Driver\\' . array_value_last(explode('\\', $connection->config()['driver']));
            $this->DriverClass = $DriverClass;
            $this->Driver = new $this->DriverClass($connection);
        }

        //Enables event tracking
        $this->Driver->getEventManager()->setEventList(new EventList());
    }

    /**
     * Internal method to get all records from the database
     * @return array<string, array>
     */
    final protected function getAllRecords(): array
    {
        foreach (['Articles', 'Comments'] as $name) {
            $records[$name] = $this->{$name}->find()->enableHydration(false)->toArray();
        }

        return $records;
    }

    /**
     * @return void
     * @throws \Exception
     * @uses \DatabaseBackup\Driver\Driver::export()
     */
    public function testExport(): void
    {
        $backup = $this->getAbsolutePath('example.sql');
        $this->assertFileDoesNotExist($backup);
        $this->Driver->dispatchEvent('Backup.beforeExport');
        $this->assertTrue($this->Driver->export($backup));
        $this->assertFileExists($backup);
    }

    /**
     * Test for `export()` and `import()` methods. It tests that the backup is properly exported and then imported.
     * @return void
     * @throws \Exception
     * @uses \DatabaseBackup\Driver\Driver::import()
     * @uses \DatabaseBackup\Driver\Driver::export()
     */
    public function testExportAndImport(): void
    {
        foreach (DATABASE_BACKUP_EXTENSIONS as $extension) {
            $backup = uniqid('example_');
            $backup = $this->getAbsolutePath($extension ? $backup . '.' . $extension : $backup);

            //Initial records. 3 articles and 6 comments
            $initial = $this->getAllRecords();
            $this->assertCount(3, $initial['Articles']);
            $this->assertCount(6, $initial['Comments']);

            //Exports backup and deletes article with ID 2 and comment with ID 4
            $this->Driver->dispatchEvent('Backup.beforeExport');
            $this->assertTrue($this->Driver->export($backup));
            $this->Driver->dispatchEvent('Backup.afterExport');
            $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
            $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

            //Records after delete. 2 articles and 5 comments
            $afterDelete = $this->getAllRecords();
            $this->assertCount(count($initial['Articles']) - 1, $afterDelete['Articles']);
            $this->assertCount(count($initial['Comments']) - 1, $afterDelete['Comments']);

            //Imports backup. Now initial records are the same of final records
            $this->Driver->dispatchEvent('Backup.beforeImport');
            $this->assertTrue($this->Driver->import($backup));
            $this->Driver->dispatchEvent('Backup.afterImport');
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
            $this->assertSame(2, collection($diff['Articles'])->extract('id')->first());
            $this->assertSame(4, collection($diff['Comments'])->extract('id')->first());
        }
    }

    /**
     * @return void
     * @throws \ReflectionException|\ErrorException
     * @uses \DatabaseBackup\Driver\Driver::_getExportExecutable()
     */
    public function testGetExportExecutable(): void
    {
        $this->assertNotEmpty($this->invokeMethod($this->Driver, '_getExportExecutable', ['backup.sql']));

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_getExportExecutable', [$filename]);
            $expected = sprintf(' | %s > %s', escapeshellarg($this->Driver->getBinary($compression)), escapeshellarg($filename));
            $this->assertStringEndsWith($expected, $result);
        }
    }

    /**
     * @return void
     * @throws \Exception
     * @uses \DatabaseBackup\Driver\Driver::import()
     */
    public function testImport(): void
    {
        $backup = $this->getAbsolutePath('example.sql');
        $this->Driver->dispatchEvent('Backup.beforeExport');
        $this->assertTrue($this->Driver->export($backup));
        $this->Driver->dispatchEvent('Backup.afterExport');
        $this->Driver->dispatchEvent('Backup.beforeImport');
        $this->assertTrue($this->Driver->import($backup));
    }

    /**
     * @return void
     * @throws \ReflectionException|\ErrorException
     * @uses \DatabaseBackup\Driver\Driver::_getImportExecutable()
     */
    public function testGetImportExecutable(): void
    {
        $this->assertNotEmpty($this->invokeMethod($this->Driver, '_getImportExecutable', ['backup.sql']));

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_getImportExecutable', [$filename]);
            $expected = sprintf('%s -dc %s | ', escapeshellarg($this->Driver->getBinary($compression)), escapeshellarg($filename));
            $this->assertStringStartsWith($expected, $result);
        }
    }
}
