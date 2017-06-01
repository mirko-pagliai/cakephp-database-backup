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
 */
namespace MysqlBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use MysqlBackup\Utility\BackupExport;
use MysqlBackup\Utility\BackupImport;

/**
 * BackupExportWithRecordsTest class
 */
class BackupExportAndImportTest extends TestCase
{
    /**
     * @var \Cake\ORM\Table
     */
    protected $Articles;

    /**
     * @var \MysqlBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \MysqlBackup\Utility\$BackupImport
     */
    protected $BackupImport;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Comments;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = ['core.articles', 'core.comments'];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->BackupExport = new BackupExport;
        $this->BackupImport = new BackupImport;

        $this->Articles = TableRegistry::get('Articles');
        $this->Comments = TableRegistry::get('Comments');
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Articles, $this->BackupExport, $this->BackupImport, $this->Comments);

        //Deletes all backups
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Internal method to get all records from the database
     * @return array
     */
    protected function _allRecords()
    {
        return [
            'Articles' => $this->Articles->find()->enableHydration(false)->toArray(),
            'Comments' => $this->Comments->find()->enableHydration(false)->toArray(),
        ];
    }

    /**
     * Internal method. Test for `export()` and `import()` methods
     * @param bool|string $compression Compression
     */
    protected function _testExportAndImport($compression)
    {
        //Initial records. 3 articles and 6 comments
        $initial = $this->_allRecords();
        $this->assertEquals(['Articles', 'Comments'], array_keys($initial));
        $this->assertEquals(3, count($initial['Articles']));
        $this->assertEquals(6, count($initial['Comments']));

        //Exports backup
        $backup = $this->BackupExport->compression($compression)->export();

        //Deletes article with ID 2 and comment with ID 4
        $this->Articles->delete($this->Articles->get(2), ['atomic' => false]);
        $this->Comments->delete($this->Comments->get(4), ['atomic' => false]);

        //Records after delete. 2 articles and 5 comments
        $deleted = $this->_allRecords();
        $this->assertEquals(['Articles', 'Comments'], array_keys($deleted));
        $this->assertEquals(2, count($deleted['Articles']));
        $this->assertEquals(5, count($deleted['Comments']));

        //Imports backup
        $this->BackupImport->filename($backup)->import();

        //Now initial records are the same of final records
        $final = $this->_allRecords();
        $this->assertEquals($initial, $final);

        //Gets the difference (`$diff`) between records after delete
        //  (`$deleted`)and records after import (`$final`)
        $diff = $final;

        foreach ($final as $model => $finalValues) {
            foreach ($finalValues as $finalKey => $finalValue) {
                foreach ($deleted[$model] as $deletedValue) {
                    if ($finalValue == $deletedValue) {
                        unset($diff[$model][$finalKey]);
                    }
                }
            }
        }

        $this->assertEquals(['Articles', 'Comments'], array_keys($diff));
        $this->assertEquals(1, count($diff['Articles']));
        $this->assertEquals(1, count($diff['Comments']));

        //Difference is article with ID 2 and comment with ID 4
        $this->assertEquals(2, collection($diff['Articles'])->extract('id')->first());
        $this->assertEquals(4, collection($diff['Comments'])->extract('id')->first());
    }

    /**
     * Test for `export()` and `import()` methods
     * @test
     */
    public function testExportAndImport()
    {
        $this->_testExportAndImport(false);
        $this->_testExportAndImport('bzip2');
        $this->_testExportAndImport('gzip');
    }
}
