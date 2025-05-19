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

use DatabaseBackup\Executor\Executor;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * ExecutorTest.
 */
#[CoversClass(Executor::class)]
class ExecutorTest extends TestCase
{
    #[Test]
    public function testImplementedEvents(): void
    {
        $Executor = new class extends Executor {};
        $result = $Executor->implementedEvents();

        $this->assertContains('beforeExport', $result);
        $this->assertContains('afterExport', $result);
        $this->assertContains('beforeImport', $result);
        $this->assertContains('afterImport', $result);
    }
}