<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase\Console;

use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Console\Command;
use DatabaseBackup\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * CommandTest.
 */
#[CoversClass(Command::class)]
class CommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var \DatabaseBackup\Console\Command
     */
    protected Command $Command;

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        $this->Command = new class extends Command {
        };
    }

    #[Test]
    #[TestWith(['backup.sql', 'backup.sql'])]
    #[TestWith(['backup.sql', ROOT . 'backup.sql'])]
    #[TestWith(['backups/backup.sql', ROOT . 'backups/backup.sql'])]
    #[TestWith([TMP . 'backup.sql', TMP . 'backup.sql'])]
    #[TestWith(['/anotherDir/backup.sql', '/anotherDir/backup.sql'])]
    public function testMakeRelativeFilename(string $expectedRelativeFilename, string $filename): void
    {
        $result = $this->Command->makeRelativeFilename($filename);
        $this->assertSame($expectedRelativeFilename, $result);
    }

    #[Test]
    public function testExecute(): void
    {
        $expectedConnection = ConnectionManager::get('test');

        $this->_out = new StubConsoleOutput();
        $this->_err = new StubConsoleOutput();

        $result = $this->Command->run(argv: [], io: new ConsoleIo($this->_out, $this->_err));

        $this->assertNull($result);
        $this->assertOutputContains('Connection: ' . $expectedConnection->config()['name']);
        $this->assertOutputContains('Driver: ' . $expectedConnection->getDriver()::class);
        $this->assertErrorEmpty();
    }

    #[Test]
    public function testExecuteWithNoExistingConnection(): void
    {
        $this->expectException(StopException::class);
        $this->expectExceptionMessage('The datasource configuration `noExisting` was not found.');
        $this->Command->run(
            argv: ['--connection=noExisting'],
            io: new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput())
        );
    }
}
