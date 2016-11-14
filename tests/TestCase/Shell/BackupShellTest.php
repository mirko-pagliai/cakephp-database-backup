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
use MysqlBackup\Utility\BackupManager;

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
     * Creates some backups, waiting a second for each
     * @return array
     */
    protected function _createSomeBackupsWithSleep()
    {
        $instance = new BackupExport();
        $instance->filename('backup.sql')->export();
        sleep(1);
        $instance->filename('backup.sql.bz2')->export();
        sleep(1);
        $instance->filename('backup.sql.gz')->export();

        return BackupManager::index();
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
     * Test for `export()` method, with the `compression` option
     * @test
     */
    public function testExportWithCompression()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with($this->callback(function ($output) {
                $pattern = '/^\<success\>Backup `%sbackup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/';
                $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

                return preg_match($pattern, $output);
            }));

        $this->BackupShell->params = ['compression' => 'none'];
        $this->BackupShell->export();
    }

    /**
     * Test for `export()` method, with the `filename` option
     * @test
     */
    public function testExportWithFilename()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with($this->callback(function ($output) {
                $pattern = '/^\<success\>Backup `%sbackup\.sql` has been exported\<\/success\>$/';
                $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

                return preg_match($pattern, $output);
            }));

        $this->BackupShell->params = ['filename' => 'backup.sql'];
        $this->BackupShell->export();
    }

    /**
     * Test for `export()` method, with the `rotate` option
     * @test
     * @uses _createSomeBackupsWithSleep()
     */
    public function testExportWithRotate()
    {
        //Creates some backups, waiting a second for each
        $this->_createSomeBackupsWithSleep();

        sleep(1);

        $this->io->expects($this->at(0))
            ->method('out')
            ->with($this->callback(function ($output) {
                $pattern = '/^\<success\>Backup `%slast\.sql` has been exported\<\/success\>$/';
                $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

                return preg_match($pattern, $output);
            }));

        $this->io->expects($this->at(1))
            ->method('verbose')
            ->with('Backup `backup.sql` has been deleted', 1);

        $this->io->expects($this->at(2))
            ->method('out')
            ->with('<success>Deleted backup files: 1</success>', 1);

        $this->BackupShell->params = ['rotate' => 3, 'filename' => 'last.sql'];
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
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        //Exports a database
        $backup = (new BackupExport())->filename('backup.sql')->export();

        $this->io->expects($this->once())
            ->method('out')
            ->with('<success>Backup `' . $backup . '` has been imported</success>', 1);

        $this->BackupShell->import($backup);
    }

    /**
     * Test for `import()` method, with a no existing filename
     * @expectedException Cake\Console\Exception\StopException
     * @test
     */
    public function testImportWithNoExistingFilename()
    {
        $this->BackupShell->import('/noExistingDir/backup.sql');
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
     * Test for `rotate()` method
     * @test
     * @uses _createSomeBackupsWithSleep()
     */
    public function testRotate()
    {
        //Creates some backups, waiting a second for each
        $this->_createSomeBackupsWithSleep();

        $this->io->expects($this->at(0))
            ->method('verbose')
            ->with('Backup `backup.sql.bz2` has been deleted', 1);

        $this->io->expects($this->at(1))
            ->method('verbose')
            ->with('Backup `backup.sql` has been deleted', 1);

        $this->io->expects($this->at(2))
            ->method('out')
            ->with('<success>Deleted backup files: 2</success>', 1);

        $this->BackupShell->rotate(1);
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @expectedException Cake\Console\Exception\StopException
     * @test
     */
    public function testRotateInvalidValue()
    {
        $this->BackupShell->rotate(-1);
    }

    /**
     * Test for `rotate()` method, with no backups to be deleted
     * @test
     */
    public function testRotateNoBackupsToBeDeleted()
    {
        $this->io->expects($this->once())
            ->method('verbose')
            ->with('No backup has been deleted', 1);

        $this->BackupShell->rotate(1);
    }

    /**
     * Test for `getOptionParser()` method
     * @test
     */
    public function testGetOptionParser()
    {
        $parser = $this->BackupShell->getOptionParser();

        $this->assertEquals('Cake\Console\ConsoleOptionParser', get_class($parser));
        $this->assertEquals(['export', 'import', 'index', 'rotate'], array_keys($parser->subcommands()));
    }
}
