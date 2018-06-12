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

use Cake\Core\Configure;
use DatabaseBackup\Shell\BackupShell;
use DatabaseBackup\TestSuite\ConsoleIntegrationTestCase;
use Tools\TestSuite\TestCaseTrait;

/**
 * BackupShellTest class
 */
class BackupShellTest extends ConsoleIntegrationTestCase
{
    use TestCaseTrait;

    /**
     * @var \DatabaseBackup\Shell\BackupShell
     */
    protected $BackupShell;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->BackupShell = new BackupShell;
    }

    /**
     * Test for `_welcome()` method
     * @test
     */
    public function testWelcome()
    {
        $this->exec('database_backup.backup index');

        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
    }

    /**
     * Test for `deleteAll()` method
     * @test
     */
    public function testDeleteAll()
    {
        //For now, no backup to be deleted
        $this->exec('database_backup.backup delete_all -v');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('No backup has been deleted');

        //Creates some backups
        $this->createSomeBackups(true);
        $this->exec('database_backup.backup delete_all -v');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup `backup.sql.gz` has been deleted');
        $this->assertOutputContains('Backup `backup.sql.bz2` has been deleted');
        $this->assertOutputContains('Backup `backup.sql` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 3</success>');
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $target = preg_quote(Configure::read(DATABASE_BACKUP . '.target') . DS, '/');

        //Exports, without params
        $this->exec('database_backup.backup export');
        $this->assertExitWithSuccess();
        $this->assertRegExp(
            '/^\<success\>Backup `' . $target . 'backup_test_\d+\.sql` has been exported\<\/success\>$/',
            $this->_out->messages()[3]
        );

        sleep(1);

        //Exports, with `compression` param
        $this->exec('database_backup.backup export --compression bzip2');
        $this->assertExitWithSuccess();
        $this->assertRegExp(
            '/^\<success\>Backup `' . $target . 'backup_test_\d+\.sql\.bz2` has been exported\<\/success\>$/',
            $this->_out->messages()[3]
        );

        sleep(1);

        //Exports, with `filename` param
        $this->exec('database_backup.backup export --filename backup.sql');
        $this->assertExitWithSuccess();
        $this->assertRegExp(
            '/^\<success\>Backup `' . $target . 'backup.sql` has been exported\<\/success\>$/',
            $this->_out->messages()[3]
        );

        sleep(1);

        //Exports, with `rotate` param
        $this->exec('database_backup.backup export --rotate 3 -v');
        $this->assertExitWithSuccess();
        $this->assertRegExp(
            '/^\<success\>Backup `' . $target . 'backup_test_\d+\.sql` has been exported\<\/success\>$/',
            $this->_out->messages()[3]
        );
        $this->assertRegExp('/^Backup `backup_test_\d+\.sql` has been deleted$/', $this->_out->messages()[4]);
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');

        sleep(1);

        //Exports, with `send` param
        $this->exec('database_backup.backup export --send mymail@example.com');
        $this->assertExitWithSuccess();
        $this->assertRegExp(
            '/^\<success\>Backup `' . $target . 'backup_test_\d+\.sql` has been exported\<\/success\>$/',
            $this->_out->messages()[3]
        );
        $this->assertRegExp(
            '/^\<success\>Backup `' . $target . 'backup_test_\d+\.sql` was sent via mail\<\/success\>$/',
            $this->_out->messages()[4]
        );
    }

    /**
     * Test for `export()` method, with an invalid option value
     * @test
     */
    public function testExportInvalidOptionValue()
    {
        $this->exec('database_backup.backup export --filename /noExistingDir/backup.sql');
        $this->assertExitWithError();
    }

    /**
     * Test for `index()` method
     * @test
     */
    public function testIndex()
    {
        //For now, no backup to index
        $this->exec('database_backup.backup index');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup files found: 0');

        //Creates some backups
        $this->createSomeBackups(true);
        $this->exec('database_backup.backup index');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup files found: 3');

        //Gets and splits the output rows about backup files
        $backups = collection(array_slice($this->_out->messages(), 7, 3))->map(function ($row) {
            return preg_split('/\s*\|\s*/', trim($row, '| '));
        })->toArray();

        foreach ($backups as $backup) {
            $this->assertRegExp('/^backup\.sql(\.(bz2|gz))?$/', $backup[0]);
            $this->assertRegExp('/^sql(\.(bz2|gz))?$/', $backup[1]);
            $this->assertContains($backup[2], ['bzip2', 'gzip', '']);
            $this->assertRegExp('/^[\d\.,]+ (Bytes|KB)$/', $backup[3]);
            $this->assertRegExp('/^[\d\/]+, [\d:]+ (A|P)M$/', $backup[4]);
        }
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        //Exports a database
        $backup = $this->createBackup();

        $this->exec('database_backup.backup import ' . $backup);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('<success>Backup `' . $backup . '` has been imported</success>');
    }

    /**
     * Test for `import()` method, with a no existing filename
     * @test
     */
    public function testImportWithNoExistingFilename()
    {
        $this->exec('database_backup.backup import /noExistingDir/backup.sql');
        $this->assertExitWithError();
    }

    /**
     * Test for `main()` method. As for `index()` with no backups
     * @test
     */
    public function testMain()
    {
        $this->exec('database_backup.backup main');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup files found: 0');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        //For now, no backup to be deleted
        $this->exec('database_backup.backup rotate 1 -v');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('No backup has been deleted');

        //Creates some backups
        $this->createSomeBackups(true);
        $this->exec('database_backup.backup rotate 1 -v');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup `backup.sql.bz2` has been deleted');
        $this->assertOutputContains('Backup `backup.sql` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 2</success>');
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @test
     */
    public function testRotateInvalidValue()
    {
        $this->exec('database_backup.backup rotate string');
        $this->assertExitWithError();
    }

    /**
     * Test for `send()` method
     * @test
     */
    public function testSend()
    {
        //Gets a backup file
        $file = $this->createBackup();

        $this->exec('database_backup.backup send ' . $file . ' recipient@example.com');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');
    }

    /**
     * Test for `send()` method, without a sender in the configuration
     * @test
     */
    public function testSendWithoutSenderInConfiguration()
    {
        Configure::write(DATABASE_BACKUP . '.mailSender', false);

        $this->exec('database_backup.backup send file.sql recipient@example.com');
        $this->assertExitWithError();
    }

    /**
     * Test for `getOptionParser()` method
     * @test
     */
    public function testGetOptionParser()
    {
        $parser = $this->BackupShell->getOptionParser();
        $this->assertInstanceOf('Cake\Console\ConsoleOptionParser', $parser);
        $this->assertEquals('Shell to handle database backups', $parser->getDescription());
        $this->assertArrayKeysEqual(['help', 'quiet', 'verbose'], $parser->options());

        $this->assertArrayKeysEqual([
            'delete_all',
            'export',
            'import',
            'index',
            'rotate',
            'send',
        ], $parser->subcommands());

        //Checks options for the "export" subcommand
        $exportSubcommandOptions = $parser->subcommands()['export']->parser()->options();
        $this->assertEquals('[-c bzip2|gzip]', $exportSubcommandOptions['compression']->usage());
        $this->assertArrayKeysEqual([
            'compression',
            'filename',
            'help',
            'quiet',
            'rotate',
            'send',
            'verbose',
        ], $exportSubcommandOptions);
    }
}
