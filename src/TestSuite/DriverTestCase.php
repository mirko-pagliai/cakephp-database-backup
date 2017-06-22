<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @since       2.0.0
 */
namespace MysqlBackup\TestSuite;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

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

        $this->Articles = TableRegistry::get('Articles', compact('connection'));
        $this->Comments = TableRegistry::get('Comments', compact('connection'));
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        //Deletes all backups
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            //@codingStandardsIgnoreLine
            @unlink($file);
        }

        unset($this->Articles, $this->Comments);
    }

    /**
     * Internal method to get all records from the database
     * @return array
     */
    final protected function allRecords()
    {
        return [
            'Articles' => $this->Articles->find()->enableHydration(false)->toArray(),
            'Comments' => $this->Comments->find()->enableHydration(false)->toArray(),
        ];
    }

    /**
     * Internal method to test `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @param object $driverInstance A driver instance
     * @param string $backup Backup relative path
     * @return void
     */
    final protected function _testExportAndImport($driverInstance, $backup = 'example.sql')
    {
        $backup = $this->getAbsolutePath($backup);

        //Initial records. 3 articles and 6 comments
        $initial = $this->allRecords();
        $this->assertEquals(3, count($initial['Articles']));
        $this->assertEquals(6, count($initial['Comments']));

        //Exports backup
        $this->assertTrue($driverInstance->export($backup));

        //Deletes article with ID 2 and comment with ID 4
        $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
        $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

        //Records after delete. 2 articles and 5 comments
        $afterDelete = $this->allRecords();
        $this->assertEquals(count($afterDelete['Articles']), count($initial['Articles']) - 1);
        $this->assertEquals(count($afterDelete['Comments']), count($initial['Comments']) - 1);

        //Imports backup
        $this->assertTrue($driverInstance->import($backup));

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

    /**
     * Test for `getCompression()` method
     * @return void
     */
    abstract public function testGetCompression();

    /**
     * Test for `getDefaultExtension()` method
     * @return void
     */
    abstract public function testGetDefaultExtension();

    /**
     * Test for `getExportExecutable()` method
     * @return void
     */
    abstract public function testGetExportExecutable();

    /**
     * Test for `getExtension()` method
     * @return void
     */
    abstract public function testGetExtension();

    /**
     * Test for `getImportExecutable()` method
     * @return void
     */
    abstract public function testGetImportExecutable();

    /**
     * Test for `getValidExtensions()` method
     * @return void
     */
    abstract public function testGetValidExtensions();

    /**
     * Test for `getValidCompressions()` method
     * @return void
     */
    abstract public function testGetValidCompressions();

    /**
     * Test for `export()` method
     * @return void
     */
    abstract public function testExport();

    /**
     * Test for `export()` method on failure
     * @return void
     */
    abstract public function testExportOnFailure();

    /**
     * Test for `import()` method
     * @return void
     */
    abstract public function testImport();

    /**
     * Test for `import()` method on failure
     * @return void
     */
    abstract public function testImportOnFailure();

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @return void
     */
    abstract public function testExportAndImport();
}
