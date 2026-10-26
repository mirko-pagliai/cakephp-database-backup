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
use DatabaseBackup\DatabaseBackupPlugin;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

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
        $expected = [
            'export',
            'database_backup.export',
            'import',
            'database_backup.import',
        ];

        $CommandCollection = new CommandCollection();
        $App = new Application(CONFIG);
        $App->addPlugin(DatabaseBackupPlugin::class);
        $App->pluginConsole($CommandCollection);

        $availableCommands = $CommandCollection->keys();
        $this->assertSame($expected, $availableCommands);
    }
}
