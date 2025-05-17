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

namespace DatabaseBackup\Test\TestCase;

use App\Application;
use Cake\Console\CommandCollection;
use DatabaseBackup\Plugin as DatabaseBackup;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * PluginTest.
 */
#[CoversClass(Plugin::class)]
class PluginTest extends TestCase
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
        $App->addPlugin(DatabaseBackup::class);
        $App->pluginConsole($CommandCollection);
        $availableCommands = $CommandCollection->keys();

        foreach ($expected as $expectedCommand) {
            $this->assertContains($expectedCommand, $availableCommands);
        }
    }
}
