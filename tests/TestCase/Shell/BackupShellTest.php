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
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupManager;

/**
 * BackupShellTest class
 */
class BackupShellTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Test for `deleteAll()` method
     * @test
     */
    public function testDeleteAll()
    {
        $this->exec('backup delete_all -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('No backup has been deleted');

        $files = $this->createSomeBackups();
        $this->exec('backup delete_all -v');
        $this->assertExitSuccess();
        foreach ($files as $file) {
            $this->assertOutputContains('Backup `' . $file . '` has been deleted');
        }
        $this->assertOutputContains('<success>Deleted backup files: 3</success>');
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $command = 'backup export -v';

        //Exports, without params
        $this->exec($command);
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` has been exported/');

        //Exports, with `compression` param
        $this->exec($command . ' --compression bzip2');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql\.bz2` has been exported/');

        //Exports, with `filename` param
        $this->exec($command . ' --filename backup.sql');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup\.sql` has been exported/');

        //Exports, with `rotate` param
        BackupManager::deleteAll();
        $files = $this->createSomeBackups();
        $this->exec($command . ' --rotate 3');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` has been exported/');
        $this->assertOutputContains('Backup `' . basename(array_value_first($files)) . '` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');

        //Exports, with `send` param
        BackupManager::deleteAll();
        $this->exec($command . ' --send mymail@example.com');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` has been exported/');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` was sent via mail/');

        //With an invalid option value
        $this->exec($command . ' --filename /noExistingDir/backup.sql');
        $this->assertErrorContains('File or directory `/noExistingDir` does not exist');
    }

    /**
     * Test for `index()` method
     * @test
     */
    public function testIndex()
    {
        $this->exec('backup index -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('Backup files found: 0');

        $this->createSomeBackups();
        $this->assertExitSuccess();
        $this->exec('backup index -v');
        $this->assertOutputContains('Backup files found: 3');
        $this->assertOutputRegExp('/backup\.sql\.gz\s+|\s+sql\.gz\s+|\s+gzip\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertOutputRegExp('/backup\.sql\.bz2\s+|\s+sql\.bz2\s+|\s+bzip2\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertOutputRegExp('/backup\.sq\s+|\s+sql\s+|\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $backup = $this->createBackup();
        $this->exec('backup import -v ' . $backup);
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('<success>Backup `' . $backup . '` has been imported</success>');

        //With a no existing filename
        $this->exec('backup import /noExistingDir/backup.sql');
        $this->assertErrorContains('File or directory `/noExistingDir/backup.sql` does not exist');
    }

    /**
     * Test for `main()` method. As for `index()` with no backups
     * @test
     */
    public function testMain()
    {
        $this->exec('backup -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('Backup files found: 0');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $this->exec('backup rotate -v 1');
        $this->assertOutputContains('No backup has been deleted');

        $this->createSomeBackups(true);
        $this->exec('backup rotate -v 1');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql\.bz2` has been deleted/');
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql` has been deleted/');
        $this->assertOutputContains('<success>Deleted backup files: 2</success>');

        //With an invalid value
        $this->exec('backup rotate a');
        $this->assertErrorContains('Invalid rotate value');
    }

    /**
     * Test for `send()` method
     * @test
     */
    public function testSend()
    {
        $file = $this->createBackup();
        $this->exec('backup send -v ' . $file . ' recipient@example.com');
        $this->assertExitSuccess();
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');

        //Without a sender in the configuration
        Configure::write('DatabaseBackup.mailSender', false);
        $this->exec('backup send ' . $file . ' recipient@example.com');
        $this->assertErrorContains('The email set for "from" is empty');
    }
}
