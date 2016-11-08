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

namespace MysqlBackup\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use MysqlBackup\Utility\BackupExport as BaseBackupExport;

/**
 * Makes public some protected methods/properties from `BackupExport`
 */
class BackupExport extends BaseBackupExport
{
    public function getCompression()
    {
        return $this->compression;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    /**
     * Test for `$connection` property
     * @test
     */
    public function testGetConnection()
    {
        $connection = (new BackupExport())->getConnection();
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');
    }

    /**
     * Test for `compression()` method
     * @test
     */
    public function testCompression()
    {
        $instance = new BackupExport();

        $this->assertFalse($instance->getCompression());

        $instance->compression('bzip2');
        $this->assertEquals('bzip2', $instance->getCompression());

        $instance->compression('gzip');
        $this->assertEquals('gzip', $instance->getCompression());

        $instance->compression(false);
        $this->assertFalse($instance->getCompression());
    }

    /**
     * Test for `compression()` method, with an invalid stringvalue
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidString()
    {
        (new BackupExport())->compression('invalidValue');
    }

    /**
     * Test for `compression()` method, with an invalid boolean value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidBool()
    {
        (new BackupExport())->compression(true);
    }
}
