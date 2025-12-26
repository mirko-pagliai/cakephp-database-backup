<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase;

use App\Application;
use Cake\Console\CommandCollection;
use Cake\TestSuite\TestCase;
use DatabaseBackup\DatabaseBackupPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * DatabaseBackupPluginTest.
 */
#[CoversClass(DatabaseBackupPlugin::class)]
class DatabaseBackupPluginTest extends TestCase
{
    /**
     * Tests that the application console includes all expected commands.
     *
     * This method checks if the commands declared in the `Command` directory
     * are properly registered and accessible in the application's `CommandCollection`.
     *
     * @return void
     */
    #[Test]
    public function testConsole(): void
    {
        //Sets the expected commands from `Command` files
        $Finder = new Finder();
        $Finder = $Finder->files()->in(ROOT . 'src/Command')->name('/.+Command\.php/');
        $expected = array_map(
            callback: fn(SplFileInfo $File): string => 'database_backup.' . lcfirst($File->getBasename('Command.php')),
            array: iterator_to_array($Finder),
        );

        $CommandCollection = new CommandCollection();
        $App = new Application(CONFIG);
        $App->addPlugin(DatabaseBackupPlugin::class);
        $App->pluginConsole($CommandCollection);
        $availableCommands = $CommandCollection->keys();

        foreach ($expected as $expectedCommand) {
            $this->assertContains($expectedCommand, $availableCommands);
        }
    }
}
