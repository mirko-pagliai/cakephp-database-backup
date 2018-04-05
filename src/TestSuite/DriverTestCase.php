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
 * @since       2.0.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Event\EventList;
use Cake\ORM\TableRegistry;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\TestSuite\TestCase;
use Tools\ReflectionTrait;

/**
 * DriverTestCase class.
 *
 * Classes with tests for driver must extend this class.
 */
abstract class DriverTestCase extends TestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Comments;

    /**
     * @var object
     */
    protected $Driver;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $connection = $this->getConnection();

        TableRegistry::clear();
        $this->Articles = TableRegistry::get('Articles', compact('connection'));
        $this->Comments = TableRegistry::get('Comments', compact('connection'));

        //Enable event tracking
        $this->Driver->getEventManager()->setEventList(new EventList);
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Articles, $this->Comments, $this->Driver);
    }

    /**
     * Internal method to get all records from the database
     * @return array
     */
    final protected function allRecords()
    {
        foreach (['Articles', 'Comments'] as $name) {
            $records[$name] = $this->$name->find()->enableHydration(false)->toArray();
        }

        return $records;
    }

    /**
     * Test for `__construct()` method
     * @return void
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof('Cake\Database\Connection', $this->getProperty($this->Driver, 'connection'));
    }

    /**
     * Test for `_exportExecutable()` method
     * @return void
     */
    abstract public function testExportExecutable();

    /**
     * Test for `export()` method on failure
     * @return void
     */
    abstract public function testExportOnFailure();

    /**
     * Test for `_importExecutable()` method
     * @return void
     */
    abstract public function testImportExecutable();

    /**
     * Test for `import()` method on failure
     * @return void
     */
    abstract public function testImportOnFailure();

    /**
     * Test for `_exportExecutableWithCompression()` method
     * @return void
     * @test
     */
    public function testExportExecutableWithCompression()
    {
        $basicExecutable = $this->invokeMethod($this->Driver, '_exportExecutable');

        //No compression
        $result = $this->invokeMethod($this->Driver, '_exportExecutableWithCompression', ['backup.sql']);
        $expected = sprintf('%s > %s%s', $basicExecutable, escapeshellarg('backup.sql'), REDIRECT_TO_DEV_NULL);
        $this->assertEquals($expected, $result);

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_exportExecutableWithCompression', [$filename]);
            $expected = sprintf(
                '%s | %s > %s%s',
                $basicExecutable,
                $this->getBinary($compression),
                escapeshellarg($filename),
                REDIRECT_TO_DEV_NULL
            );
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test for `_importExecutableWithCompression()` method
     * @return void
     * @test
     */
    public function testImportExecutableWithCompression()
    {
        $basicExecutable = $this->invokeMethod($this->Driver, '_importExecutable');

        //No compression
        $result = $this->invokeMethod($this->Driver, '_importExecutableWithCompression', ['backup.sql']);
        $expected = $basicExecutable . ' < ' . escapeshellarg('backup.sql') . REDIRECT_TO_DEV_NULL;
        $this->assertEquals($expected, $result);

        //Gzip and Bzip2 compressions
        foreach (['gzip' => 'backup.sql.gz', 'bzip2' => 'backup.sql.bz2'] as $compression => $filename) {
            $result = $this->invokeMethod($this->Driver, '_importExecutableWithCompression', [$filename]);
            $expected = sprintf(
                '%s -dc %s | %s%s',
                $this->getBinary($compression),
                escapeshellarg($filename),
                $basicExecutable,
                REDIRECT_TO_DEV_NULL
            );
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test for `export()` method
     * @return void
     * @test
     */
    public function testExport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Driver->export($backup));
        $this->assertFileExists($backup);
        $this->assertEventFired('Backup.beforeExport', $this->Driver->getEventManager());
        $this->assertEventFired('Backup.afterExport', $this->Driver->getEventManager());
    }

    /**
     * Test for `export()` method. Export is stopped because the
     *  `beforeExport()` method returns `false`
     * @return void
     * @test
     */
    public function testExportStoppedByBeforeExport()
    {
        $this->Driver = $this->getMockBuilder(get_class($this->Driver))
            ->setMethods(['beforeExport'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('beforeExport')
             ->will($this->returnValue(false));

        $backup = $this->getAbsolutePath('example.sql');

        $this->assertFalse($this->Driver->export($backup));
        $this->assertFileNotExists($backup);
    }

    /**
     * Test for `getConfig()` method
     * @return void
     * @test
     */
    public function testGetConfig()
    {
        $this->assertNotEmpty($this->Driver->getConfig());
        $this->assertTrue(is_array($this->Driver->getConfig()));
        $this->assertNotEmpty($this->Driver->getConfig('name'));
        $this->assertNull($this->Driver->getConfig('noExistingKey'));
    }

    /**
     * Test for `import()` method
     * @return void
     * @test
     */
    public function testImport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Driver->export($backup));
        $this->assertTrue($this->Driver->import($backup));
        $this->assertEventFired('Backup.beforeImport', $this->Driver->getEventManager());
        $this->assertEventFired('Backup.afterImport', $this->Driver->getEventManager());
    }

    /**
     * Test for `import()` method. Import is stopped because the
     *  `beforeImport()` method returns `false`
     * @return void
     * @test
     */
    public function testImportStoppedByBeforeExport()
    {
        $this->Driver = $this->getMockBuilder(get_class($this->Driver))
            ->setMethods(['beforeImport'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('beforeImport')
             ->will($this->returnValue(false));

        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Driver->export($backup));
        $this->assertFalse($this->Driver->import($backup));
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @return void
     * @test
     */
    public function testExportAndImport()
    {
        foreach ($this->getValidExtensions() as $extension) {
            $this->loadFixtures();

            $backup = $this->getAbsolutePath(sprintf('example.%s', $extension));

            //Initial records. 3 articles and 6 comments
            $initial = $this->allRecords();
            $this->assertEquals(3, count($initial['Articles']));
            $this->assertEquals(6, count($initial['Comments']));

            //Exports backup
            $this->assertTrue($this->Driver->export($backup));

            //Deletes article with ID 2 and comment with ID 4
            $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
            $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

            //Records after delete. 2 articles and 5 comments
            $afterDelete = $this->allRecords();
            $this->assertEquals(count($afterDelete['Articles']), count($initial['Articles']) - 1);
            $this->assertEquals(count($afterDelete['Comments']), count($initial['Comments']) - 1);

            //Imports backup
            $this->assertTrue($this->Driver->import($backup));

            //Now initial records are the same of final records
            $final = $this->allRecords();
            $this->assertEquals($initial, $final);

            //Gets the difference (`$diff`) between records after delete
            //  (`$deleted`)and records after import (`$final`)
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

            $this->assertEquals(1, count($diff['Articles']));
            $this->assertEquals(1, count($diff['Comments']));

            //Difference is article with ID 2 and comment with ID 4
            $this->assertEquals(2, collection($diff['Articles'])->extract('id')->first());
            $this->assertEquals(4, collection($diff['Comments'])->extract('id')->first());
        }
    }
}
