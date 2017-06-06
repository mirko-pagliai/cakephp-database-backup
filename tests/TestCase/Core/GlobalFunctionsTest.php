<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace MysqlBackup\Test\TestCase\Core;

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
        $result = which('cat');
        $expected = exec('which cat');
        $this->assertEquals($expected, $result);
    }
}
