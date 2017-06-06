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
     * @var \MysqlBackup\Utility\BackupExport
     */
    protected $BackupExport;

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
     * Internal method to delete all backups
     */
    protected function _deleteAllBackups()
    {
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

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

        $this->out = new ConsoleOutput();
        $this->err = new ConsoleOutput();
        $io = new ConsoleIo($this->out, $this->err);
        $io->level(2);

        $this->BackupShell = $this->getMockBuilder(BackupShell::class)
            ->setMethods(['in', '_stop'])
            ->setConstructorArgs([$io])
            ->getMock();

        $this->_deleteAllBackups();
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->BackupExport, $this->BackupShell, $this->err, $this->out);

        $this->_deleteAllBackups();
    }

    /**
     * Creates some backups
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function _createSomeBackups($sleep = false)
    {
        $this->BackupExport->filename('backup.sql')->export();

        if ($sleep) {
            sleep(1);
        }

        $this->BackupExport->filename('backup.sql.bz2')->export();

        if ($sleep) {
            sleep(1);
        }

        $this->BackupExport->filename('backup.sql.gz')->export();

        return (new BackupManager)->index();
    }

    /**
     * Test for `deleteAll()` method
     * @test
     */
    public function testDeleteAll()
    {
        //For now, no backup to be deleted
        $this->BackupShell->deleteAll();

        //Creates some backups
        $this->_createSomeBackups(true);

        $this->BackupShell->deleteAll();

        $this->assertEquals([
            'No backup has been deleted',
            'Backup `backup.sql.gz` has been deleted',
            'Backup `backup.sql.bz2` has been deleted',
            'Backup `backup.sql` has been deleted',
            '<success>Deleted backup files: 3</success>',
        ], $this->out->messages());
        $this->assertEmpty($this->err->messages());
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        //Exports, without params
        $this->BackupShell->export();

        sleep(1);

        //Exports, with `compression` param
        $this->BackupShell->params['compression'] = 'none';
        $this->BackupShell->export();

        sleep(1);

        //Exports, with `filename` param
        unset($this->BackupShell->params['compression']);
        $this->BackupShell->params['filename'] = 'backup.sql';
        $this->BackupShell->export();

        sleep(1);

        //Exports, with `rotate` param
        unset($this->BackupShell->params['filename']);
        $this->BackupShell->params['rotate'] = 3;
        $this->BackupShell->export();

        $output = $this->out->messages();

        $this->assertEquals(6, count($output));

        $this->assertRegExp(
            '/^\<success\>Backup `\/tmp\/backups\/backup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/',
            current($output)
        );
        $this->assertRegExp(
            '/^\<success\>Backup `\/tmp\/backups\/backup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/',
            next($output)
        );
        $this->assertEquals('<success>Backup `/tmp/backups/backup.sql` has been exported</success>', next($output));
        $this->assertRegExp(
            '/^\<success\>Backup `\/tmp\/backups\/backup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/',
            next($output)
        );
        $this->assertRegExp('/^Backup `backup_test_[0-9]{14}\.sql` has been deleted$/', next($output));
        $this->assertEquals('<success>Deleted backup files: 1</success>', next($output));

        $this->assertEmpty($this->err->messages());
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
     * @test
     */
    public function testIndex()
    {
        //For now, no backup to index
        $this->BackupShell->index();

        //Creates some backups
        $backups = $this->_createSomeBackups(true);

        $this->BackupShell->index();

        $output = $this->out->messages();

        $this->assertEquals(9, count($output));
        //Splits some output rows
        $output = collection($output)->map(function ($row) {
            if (preg_match('/\s*\|\s*/', $row)) {
                return array_values(array_filter(preg_split('/\s*\|\s*/', $row)));
            }

            return $row;
        })->toArray();

        $this->assertEquals('Backup files found: 0', current($output));
        $this->assertEquals('Backup files found: 3', next($output));
        $this->assertRegExp('/^[+\-]+$/', next($output));
        $this->assertEquals([
            '<info>Filename</info>',
            '<info>Extension</info>',
            '<info>Compression</info>',
            '<info>Size</info>',
            '<info>Datetime</info>',
        ], next($output));
        $this->assertRegExp('/^[+\-]+$/', next($output));
        $this->assertEquals([
            'backup.sql.gz',
            'sql.gz',
            'gzip',
            Number::toReadableSize($backups[0]->size),
            (string)$backups[0]->datetime,
        ], next($output));
        $this->assertEquals([
            'backup.sql.bz2',
            'sql.bz2',
            'bzip2',
            Number::toReadableSize($backups[1]->size),
            (string)$backups[1]->datetime,
        ], next($output));
        $this->assertEquals([
            'backup.sql',
            'sql',
            'none',
            Number::toReadableSize($backups[2]->size),
            (string)$backups[2]->datetime,
        ], next($output));
        $this->assertRegExp('/^[+\-]+$/', next($output));

        $this->assertEmpty($this->err->messages());
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        //Exports a database
        $backup = $this->BackupExport->filename('backup.sql')->export();

        $this->BackupShell->import($backup);

        $this->assertEquals([
            '<success>Backup `/tmp/backups/backup.sql` has been imported</success>',
        ], $this->out->messages());
        $this->assertEmpty($this->err->messages());
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

        $this->assertEquals([
            'Backup files found: 0',
        ], $this->out->messages());
        $this->assertEmpty($this->err->messages());
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        //For now, no backup to be deleted
        $this->BackupShell->rotate(1);

        //Creates some backups
        $this->_createSomeBackups(true);

        $this->BackupShell->rotate(1);

        $this->assertEquals([
            'No backup has been deleted',
            'Backup `backup.sql.bz2` has been deleted',
            'Backup `backup.sql` has been deleted',
            '<success>Deleted backup files: 2</success>',
        ], $this->out->messages());
        $this->assertEmpty($this->err->messages());
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
     * Test for `send()` method
     * @test
     */
    public function testSend()
    {
        //Gets a backup file
        $file = collection($this->_createSomeBackups())->extract('filename')->first();

        $this->BackupShell->send($file, 'recipient@example.com');

        $this->assertEquals([
            '<success>The backup file was sent via mail</success>'
        ], $this->out->messages());
        $this->assertEmpty($this->err->messages());
    }

    /**
     * Test for `send()` method, without a sender in the configuration
     * @test
     * @expectedException Cake\Console\Exception\StopException
     */
    public function testSendWithoutSenderInConfiguration()
    {
        Configure::write(MYSQL_BACKUP . '.mailSender', false);

        $this->BackupShell->send('file.sql', 'recipient@example.com');
    }

    /**
     * Test for `getOptionParser()` method
     * @test
     */
    public function testGetOptionParser()
    {
        $parser = $this->BackupShell->getOptionParser();

        $this->assertInstanceOf('Cake\Console\ConsoleOptionParser', $parser);
        $this->assertEquals([
            'deleteAll',
            'export',
            'import',
            'index',
            'rotate',
            'send',
        ], array_keys($parser->subcommands()));
        $this->assertEquals('Shell to handle database backups', $parser->getDescription());
        $this->assertEquals(['help', 'quiet', 'verbose'], array_keys($parser->options()));
    }
}
