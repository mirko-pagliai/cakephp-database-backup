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
use Cake\Event\EventList;
use Cake\ORM\Table;
use DatabaseBackup\TestSuite\TestCase;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    /**
     * @var \Cake\ORM\Table
     */
    protected $Articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Comments;

    /**
     * @var \DatabaseBackup\Driver\Driver
     */
    protected $Driver;

    /**
     * Driver class
     * @since 2.5.1
     * @var class-string<\DatabaseBackup\Driver\Driver>
     */
    protected $DriverClass;

    /**
     * Auto fixtures
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Name of the database connection
     * @var string
     */
    protected $connection;

    /**
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

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
            $this->$name = $this->$name ?: $this->getTable($name, compact('connection')) ?: new Table();
        }

        if (!$this->DriverClass || !$this->Driver) {
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
            $records[$name] = $this->$name->find()->enableHydration(false)->toArray();
        }

        return $records;
    }

    /**
     * Test for `export()` method
     * @return void
     * @test
     */
    public function testExport(): void
    {
        $backup = $this->getAbsolutePath('example.sql');
        $this->assertFileNotExists($backup);
        $this->assertTrue($this->Driver->export($backup));
        $this->assertFileExists($backup);
        $this->assertEventFired('Backup.beforeExport', $this->Driver->getEventManager());
        $this->assertEventFired('Backup.afterExport', $this->Driver->getEventManager());
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @return void
     * @test
     */
    public function testExportAndImport(): void
    {
        foreach (self::$validExtensions as $extension) {
            $this->loadFixtures();
            $backup = uniqid('example_');
            $backup = $this->getAbsolutePath($extension ? $backup . '.' . $extension : $backup);

            //Initial records. 3 articles and 6 comments
            $initial = $this->getAllRecords();
            $this->assertCount(3, $initial['Articles']);
            $this->assertCount(6, $initial['Comments']);

            //Exports backup and deletes article with ID 2 and comment with ID 4
            $this->assertTrue($this->Driver->export($backup));
            $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
            $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

            //Records after delete. 2 articles and 5 comments
            $afterDelete = $this->getAllRecords();
            $this->assertCount(count($initial['Articles']) - 1, $afterDelete['Articles']);
            $this->assertCount(count($initial['Comments']) - 1, $afterDelete['Comments']);

            //Imports backup. Now initial records are the same of final records
            $this->assertTrue($this->Driver->import($backup));
            $final = $this->getAllRecords();
            $this->assertEquals($initial, $final);

            //Gets the difference (`$diff`) between records after delete and
            //  records after import (`$final`)
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
            $this->assertEquals(2, collection($diff['Articles'])->extract('id')->first());
            $this->assertEquals(4, collection($diff['Comments'])->extract('id')->first());
        }
    }

    /**
     * Test for `_exportExecutable()` method
     * @return void
     */
    abstract public function testExportExecutable(): void;

    /**
     * Test for `_exportExecutableWithCompression()` method
     * @return void
     * @test
     */
    public function testExportExecutableWithCompression(): void
    {
        $basicExecutable = $this->invokeMethod($this->Driver, '_exportExecutable');

        //No compression
        $result = $this->invokeMethod($this->Driver, '_exportExecutableWithCompression', ['backup.sql']);
        $expected = sprintf('%s > %s', $basicExecutable, escapeshellarg('backup.sql'));
        $expected .= Configure::read('DatabaseBackup.redirectStderrToDevNull') ? REDIRECT_TO_DEV_NULL : '';
        $this->assertEquals($expected, $result);

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_exportExecutableWithCompression', [$filename]);
            $expected = sprintf(
                '%s | %s > %s',
                $basicExecutable,
                $this->Driver->getBinary($compression),
                escapeshellarg($filename)
            );
            $expected .= Configure::read('DatabaseBackup.redirectStderrToDevNull') ? REDIRECT_TO_DEV_NULL : '';
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test for `import()` method
     * @return void
     * @test
     */
    public function testImport(): void
    {
        $backup = $this->getAbsolutePath('example.sql');
        $this->assertTrue($this->Driver->export($backup));
        $this->assertTrue($this->Driver->import($backup));
        $this->assertEventFired('Backup.beforeImport', $this->Driver->getEventManager());
        $this->assertEventFired('Backup.afterImport', $this->Driver->getEventManager());
    }

    /**
     * Test for `_importExecutable()` method
     * @return void
     */
    abstract public function testImportExecutable(): void;

    /**
     * Test for `_importExecutableWithCompression()` method
     * @return void
     * @test
     */
    public function testImportExecutableWithCompression(): void
    {
        $basicExecutable = $this->invokeMethod($this->Driver, '_importExecutable');

        //No compression
        $result = $this->invokeMethod($this->Driver, '_importExecutableWithCompression', ['backup.sql']);
        $expected = $basicExecutable . ' < ' . escapeshellarg('backup.sql');
        $expected .= Configure::read('DatabaseBackup.redirectStderrToDevNull') ? REDIRECT_TO_DEV_NULL : '';
        $this->assertEquals($expected, $result);

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_importExecutableWithCompression', [$filename]);
            $expected = sprintf(
                '%s -dc %s | %s',
                $this->Driver->getBinary($compression),
                escapeshellarg($filename),
                $basicExecutable
            );
            $expected .= Configure::read('DatabaseBackup.redirectStderrToDevNull') ? REDIRECT_TO_DEV_NULL : '';
            $this->assertEquals($expected, $result);
        }
    }
}
