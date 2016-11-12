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
namespace MysqlBackup\Test\TestCase\Shell;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MysqlBackup\Shell\BackupShell;
use MysqlBackup\Utility\BackupExport;

/**
 * BackupShellTest class
 */
class BackupShellTest extends TestCase
{
    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->BackupShell = new BackupShell($this->io);
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        //Deletes all backups
        foreach (glob(Configure::read('MysqlBackup.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with($this->callback(function ($output) {
                $pattern = '/^\<success\>Backup `%sbackup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/';
                $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

                return preg_match($pattern, $output);
            }));

        $this->BackupShell->export();
    }

    /**
     * Test for `export()` method, with an invalid option value
     * @expectedException Cake\Console\Exception\StopException
     * @test
     */
    public function testExportInvalidOptionValue()
    {
        $this->BackupShell->params = ['filename' => '/noExistingDir/backup.sql'];
        $this->BackupShell->export();
    }

    /**
     * Test for `index()` method, with no backups
     * @test
     */
    public function testIndexNoBackups()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with('Backup files found: 0', 1);

        $this->BackupShell->index();
    }

    /**
     * Test for `main()` method. As for `index()` with no backups
     * @test
     */
    public function testMain()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with('Backup files found: 0', 1);

        $this->BackupShell->main();
    }

    /**
     * Test for `getOptionParser()` method
     * @test
     */
    public function testGetOptionParser()
    {
        $result = $this->BackupShell->getOptionParser();

        $this->assertEquals('Cake\Console\ConsoleOptionParser', get_class($result));
    }
}
