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
 */
namespace DatabaseBackup\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\Number;
use Cake\TestSuite\Stub\ConsoleOutput;
use DatabaseBackup\Shell\BackupShell;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use Reflection\ReflectionTrait;

/**
 * BackupShellTest class
 */
class BackupShellTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    protected $BackupManager;

    /**
     * @var \DatabaseBackup\Shell\BackupShell
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

        $this->BackupExport = new BackupExport;
        $this->BackupManager = new BackupManager;

        $this->out = new ConsoleOutput;
        $this->err = new ConsoleOutput;
        $io = new ConsoleIo($this->out, $this->err);
        $io->level(2);

        $this->BackupShell = $this->getMockBuilder(BackupShell::class)
            ->setMethods(['in', '_stop'])
            ->setConstructorArgs([$io])
            ->getMock();
    }

    /**
     * Test for `_welcome()` method
     * @test
     */
    public function testWelcome()
    {
        $this->invokeMethod($this->BackupShell, '_welcome');

        $messages = $this->out->messages();

        $this->assertRegExp('/^Connection: test/', $messages[6]);
        $this->assertEquals('Driver: Mysql', $messages[7]);
        $this->assertRegExp('/^\-+$/', $messages[8]);
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
        $this->createSomeBackups(true);

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

        sleep(1);

        //Exports, with `rotate` param
        unset($this->BackupShell->params['rotate']);
        $this->BackupShell->params['send'] = 'mymail@example.com';
        $this->BackupShell->export();

        $output = $this->out->messages();

        $this->assertEquals(8, count($output));

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
        $this->assertRegExp(
            '/^\<success\>Backup `\/tmp\/backups\/backup_test_[0-9]{14}\.sql` has been exported\<\/success\>$/',
            next($output)
        );
        $this->assertRegExp(
            '/^\<success\>Backup `\/tmp\/backups\/backup_test_[0-9]{14}\.sql` was sent via mail\<\/success\>$/',
            next($output)
        );

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
        $this->createSomeBackups(true);
        $backups = $this->BackupManager->index();

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
        $this->createSomeBackups(true);

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
        $file = $this->createBackup();

        $this->BackupShell->send($file, 'recipient@example.com');

        $this->assertEquals([
            '<success>Backup `/tmp/backups/backup.sql` was sent via mail</success>',
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
        Configure::write(DATABASE_BACKUP . '.mailSender', false);

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

        //Checks "compression" options for the "export" subcommand
        $this->assertEquals('[-c bzip2|gzip]', $parser->subcommands()['export']->parser()->options()['compression']->usage());

        $this->assertEquals('Shell to handle database backups', $parser->getDescription());
        $this->assertEquals(['help', 'quiet', 'verbose'], array_keys($parser->options()));
    }
}
