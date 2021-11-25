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

use Cake\I18n\I18n;
use DatabaseBackup\TestSuite\TestCase;

/**
 * I18nTest class
 */
class I18nTest extends TestCase
{
    /**
     * Tests I18n translations
     * @test
     */
    public function testI18nConstant(): void
    {
        $translator = I18n::getTranslator('database_backup', 'it');
        $this->assertNotEquals('Exports a database backup', $translator->translate('Exports a database backup'));
    }
}
