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

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\Number;
use Cake\TestSuite\Stub\ConsoleOutput;
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
     * @var \MysqlBackup\Shell\BackupShell
     */
    protected $BackupShell;

    /**
     * @var \Cake\TestSuite\Stub\ConsoleOutput
     */
    protected $err;

    /**
     * @var \Cake\TestSuite\Stub\ConsoleOutput
     */
    protected $out;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->out = new ConsoleOutput();
        $this->err = new ConsoleOutput();
        $io = new ConsoleIo($this->out, $this->err);
        $io->level(2);

        $this->BackupShell = $this->getMockBuilder(BackupShell::class)
            ->setMethods(['in', '_stop'])
            ->setConstructorArgs([$io])
            ->getMock();
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

        unset($this->BackupShell, $this->err, $this->out);
    }

    /**
     * Creates some backups
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function _createSomeBackups($sleep = false)
    {
        $instance = new BackupExport();
        $instance->filename('backup.sql')->export();

        if ($sleep) {
            sleep(1);
        }

        $instance->filename('backup.sql.bz2')->export();

        if ($sleep) {
            sleep(1);
        }

        $instance->filename('backup.sql.gz')->export();

        return BackupManager::index();
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $this->BackupShell->export();
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));

        $pattern = '/^\<success\>Backup `%sbackup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/';
        $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

        $this->assertRegExp($pattern, $output[0]);
    }

    /**
     * Test for `export()` method, with the `compression` option
     * @test
     */
    public function testExportWithCompression()
    {
        $this->BackupShell->params['compression'] = 'none';
        $this->BackupShell->export();
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));

        $pattern = '/^\<success\>Backup `%sbackup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/';
        $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

        $this->assertRegExp($pattern, $output[0]);
    }

    /**
     * Test for `export()` method, with the `filename` option
     * @test
     */
    public function testExportWithFilename()
    {
        $this->BackupShell->params['filename'] = 'backup.sql';
        $this->BackupShell->export();
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));

        $pattern = '/^\<success\>Backup `%sbackup\.sql` has been exported\<\/success\>$/';
        $pattern = sprintf($pattern, preg_quote(Configure::read('MysqlBackup.target') . DS, '/'));

        $this->assertRegExp($pattern, $output[0]);
    }

    /**
     * Test for `export()` method, with the `rotate` option
     * @test
     * @uses _createSomeBackups()
     */
    public function testExportWithRotate()
    {
        //Creates some backups
        $this->_createSomeBackups(true);

        sleep(1);

        $this->BackupShell->params['rotate'] = 3;
        $this->BackupShell->params['filename'] = 'last.sql';
        $this->BackupShell->export();

        $this->assertEquals([
            '<success>Backup `/tmp/backups/last.sql` has been exported</success>',
            'Backup `backup.sql` has been deleted',
            '<success>Deleted backup files: 1</success>',
        ], $this->out->messages());
    }

    /**
     * Test for `export()` method, with an invalid option value
     * @expectedException Cake\Console\Exception\StopException
     * @test
     */
    public function testExportInvalidOptionValue()
    {
        $this->BackupShell->params['filename'] = '/noExistingDir/backup.sql';
        $this->BackupShell->export();
    }

    /**
     * Test for `index()` method
     * @uses _createSomeBackups()
     * @test
     */
    public function testIndex()
    {
        //Creates some backups
        $backups = $this->_createSomeBackups(true);

        $this->BackupShell->index();
        $output = $this->out->messages();

        $this->assertEquals(8, count($output));

        //Splits some output rows
        foreach ([2, 4, 5, 6] as $key) {
            $output[$key] = array_values(array_filter(preg_split('/\s*\|\s*/', $output[$key])));
        }

        $this->assertEquals('Backup files found: 3', $output[0]);
        $this->assertRegExp('/^[+\-]+$/', $output[1]);
        $this->assertEquals([
            '<info>Filename</info>',
            '<info>Extension</info>',
            '<info>Compression</info>',
            '<info>Size</info>',
            '<info>Datetime</info>',
        ], $output[2]);
        $this->assertRegExp('/^[+\-]+$/', $output[3]);
        $this->assertEquals([
            'backup.sql.gz',
            'sql.gz',
            'gzip',
            Number::toReadableSize($backups[0]->size),
            (string)$backups[0]->datetime,
        ], $output[4]);
        $this->assertEquals([
            'backup.sql.bz2',
            'sql.bz2',
            'bzip2',
            Number::toReadableSize($backups[1]->size),
            (string)$backups[1]->datetime,
        ], $output[5]);
        $this->assertEquals([
            'backup.sql',
            'sql',
            'none',
            Number::toReadableSize($backups[2]->size),
            (string)$backups[2]->datetime,
        ], $output[6]);
        $this->assertRegExp('/^[+\-]+$/', $output[7]);
    }

    /**
     * Test for `index()` method, with no backups
     * @test
     */
    public function testIndexNoBackups()
    {
        $this->BackupShell->index();
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));
        $this->assertEquals('Backup files found: 0', $output[0]);
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        //Exports a database
        $backup = (new BackupExport())->filename('backup.sql')->export();

        $this->BackupShell->import($backup);
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));
        $this->assertEquals('<success>Backup `' . $backup . '` has been imported</success>', $output[0]);
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
        $this->BackupShell->main();
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));
        $this->assertEquals('Backup files found: 0', $output[0]);
    }

    /**
     * Test for `rotate()` method
     * @test
     * @uses _createSomeBackups()
     */
    public function testRotate()
    {
        //Creates some backups
        $this->_createSomeBackups(true);

        $this->BackupShell->rotate(1);

        $this->assertEquals([
            'Backup `backup.sql.bz2` has been deleted',
            'Backup `backup.sql` has been deleted',
            '<success>Deleted backup files: 2</success>',
        ], $this->out->messages());
    }

    /**
     * Test for `rotate()` method. Verbose level
     * @test
     * @uses _createSomeBackups()
     */
    public function testRotateVerboseLevel()
    {
        //Creates some backups
        $this->_createSomeBackups(true);

        $this->BackupShell->rotate(1);
        $output = $this->out->messages();

        $this->assertEquals(3, count($output));
        $this->assertEquals('Backup `backup.sql.bz2` has been deleted', $output[0]);
        $this->assertEquals('Backup `backup.sql` has been deleted', $output[1]);
        $this->assertEquals('<success>Deleted backup files: 2</success>', $output[2]);
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
        $this->BackupShell->rotate(1);
        $output = $this->out->messages();

        $this->assertEquals(1, count($output));
        $this->assertEquals('No backup has been deleted', $output[0]);
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
