<?php
declare(strict_types=1);

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

namespace DatabaseBackup\Test\TestCase\Executor;

use App\Database\FakeConnection;
use Cake\Core\Configure;
use Cake\Database\Driver\Mariadb;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\MariadbExecutor;
use DatabaseBackup\OperationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * MariadbExecutorTest.
 *
 * It extends `MysqlExecutorTest`, so the tests of `MysqlExecutor` are also run for `MariadbExecutor`. The tests that
 *  cannot be shared are overridden here.
 */
#[CoversClass(MariadbExecutor::class)]
class MariadbExecutorTest extends MysqlExecutorTest
{
    /**
     * @inheritDoc
     */
    protected string $executorClass = MariadbExecutor::class;

    /**
     * @link \DatabaseBackup\Executor\MariadbExecutor::getBinaryName()
     */
    #[Test]
    #[TestWith(['mariadb-dump', OperationType::Export])]
    #[TestWith(['mariadb', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinaryName, OperationType $OperationType): void
    {
        $MariadbExecutor = new MariadbExecutor(Connection: new FakeConnection(), OperationType: $OperationType);
        $this->assertSame($expectedBinaryName, $MariadbExecutor->getBinaryName());
    }

    /**
     * Tests that the command starts with the one configured for the `Mariadb` driver.
     *
     * The default commands of the `Mysql` and `Mariadb` drivers are the same, so a custom command is set to tell them
     *  apart.
     *
     * @link \DatabaseBackup\Executor\Executor::getCommand()
     */
    #[Test]
    #[TestWith(['DatabaseBackup.Mariadb.export', OperationType::Export])]
    #[TestWith(['DatabaseBackup.Mariadb.import', OperationType::Import])]
    public function testGetCommand(string $configKey, OperationType $OperationType): void
    {
        $originalCommand = Configure::readOrFail($configKey);
        Configure::write($configKey, 'custom-command');

        $MariadbExecutor = new MariadbExecutor(
            Connection: new FakeConnection(['driver' => Mariadb::class]),
            OperationType: $OperationType,
        );

        $this->assertStringStartsWith('custom-command', $MariadbExecutor->getCommand(Compression::None));

        Configure::write($configKey, $originalCommand);
    }

    /**
     * @link \DatabaseBackup\Executor\MariadbExecutor::runProcess()
     */
    #[Test]
    public function testRunProcessDeletesAuthFile(): void
    {
        $MariadbExecutor = new class (new FakeConnection(), OperationType::Export) extends MariadbExecutor {
            public function getCommand(Compression $Compression): string
            {
                return 'exit 0';
            }

            public function findBinary(Compression|string $binaryName): string
            {
                return '/usr/bin/mariadb-dump';
            }
        };
        $authFile = $MariadbExecutor->authFile;

        $MariadbExecutor->runProcess('backup.sql');

        // `deleteAuthFile()` resets the property, so the path of the file with the authentication data changes
        $this->assertNotSame($authFile, $MariadbExecutor->authFile);
    }

    /**
     * @link \DatabaseBackup\Executor\MariadbExecutor::runProcess()
     */
    #[Test]
    public function testRunProcessDeletesAuthFileOnFailure(): void
    {
        $MariadbExecutor = new class (new FakeConnection(), OperationType::Export) extends MariadbExecutor {
            public function getCommand(Compression $Compression): string
            {
                return 'exit 1';
            }

            public function findBinary(Compression|string $binaryName): string
            {
                return '/usr/bin/mariadb-dump';
            }
        };
        $authFile = $MariadbExecutor->authFile;

        $this->expectException(ProcessFailedException::class);

        try {
            $MariadbExecutor->runProcess('backup.sql');
        } finally {
            // `deleteAuthFile()` resets the property, so the path of the file with the authentication data changes
            $this->assertNotSame($authFile, $MariadbExecutor->authFile);
        }
    }

    /**
     * @link \DatabaseBackup\Executor\MariadbExecutor::beforeExport()
     * @link \DatabaseBackup\Executor\MariadbExecutor::beforeImport()
     */
    #[Test]
    #[TestWith([OperationType::Export, 'Backup.beforeExport', '[mysqldump]'])]
    #[TestWith([OperationType::Import, 'Backup.beforeImport', '[client]'])]
    public function testBeforeEvents(OperationType $OperationType, string $eventName, string $expectedSection): void
    {
        $MariadbExecutor = new class (new FakeConnection(), $OperationType) extends MariadbExecutor {
            /**
             * @var array<string>
             */
            public array $contents = [];

            public function writeAuthFile(string $content): bool
            {
                $this->contents[] = $content;

                return true;
            }
        };

        $MariadbExecutor->dispatchEvent($eventName);

        $expectedContent = $expectedSection . PHP_EOL .
            'user="{{USER}}"' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host="{{HOST}}"';
        $this->assertSame([$expectedContent], $MariadbExecutor->contents);
    }
}
