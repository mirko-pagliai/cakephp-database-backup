<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase;

use Cake\Console\CommandCollection;
use Cake\TestSuite\TestCase;
use DatabaseBackup\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * PluginTest.
 */
#[CoversClass(Plugin::class)]
class PluginTest extends TestCase
{
    #[Test]
    public function testConsole(): void
    {
        $expectedCommands = [
            'database_backup.export' => 'DatabaseBackup\Command\ExportCommand',
            'database_backup.import' => 'DatabaseBackup\Command\ImportCommand',
            'database_backup.index' => 'DatabaseBackup\Command\IndexCommand',
        ];

        $Plugin = new Plugin();
        $CommandCollection = $Plugin->console(new CommandCollection());

        foreach ($expectedCommands as $expectedName => $expectedCommand) {
            $this->assertSame($expectedCommand, $CommandCollection->get($expectedName));
        }
    }
}
