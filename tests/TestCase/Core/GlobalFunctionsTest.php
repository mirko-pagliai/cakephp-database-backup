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
namespace DatabaseBackup\Test\TestCase\Core;

use Cake\TestSuite\TestCase;

/**
 * GlobalFunctionsTest class
 */
class GlobalFunctionsTest extends TestCase
{
    /**
     * Test for `isPositive()` global function
     * @return void
     * @test
     */
    public function testIsPositive()
    {
        $this->assertTrue(isPositive(1));
        $this->assertFalse(isPositive(0));
        $this->assertFalse(isPositive(-1));
        $this->assertFalse(isPositive(1.1));
    }

    /**
     * Test for `rtr()` global function
     * @return void
     * @test
     */
    public function testRtr()
    {
        $result = rtr(ROOT . 'my' . DS . 'folder');
        $expected = 'my' . DS . 'folder';
        $this->assertEquals($expected, $result);

        $result = rtr('my' . DS . 'folder');
        $expected = 'my' . DS . 'folder';

        $this->assertEquals($expected, $result);
        $result = rtr(DS . 'my' . DS . 'folder');
        $expected = DS . 'my' . DS . 'folder';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `which()` global function
     * @return void
     * @test
     */
    public function testWhich()
    {
        $expected = exec('which cat');
        $this->assertEquals($expected, which('cat'));

        $this->assertNull(which('noExistingBin'));
    }
}
