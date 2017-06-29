<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace DatabaseBackup\Test\TestCase\Driver;

use Cake\ORM\TableRegistry;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;

/**
 * DriverTest class
 */
class DriverTest extends TestCase
{
    use BackupTrait;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Comments;

    /**
     * @var \DatabaseBackup\Driver\Mysql
     */
    protected $Mysql;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.articles',
        'core.comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Articles = TableRegistry::get('Articles');
        $this->Comments = TableRegistry::get('Comments');

        $this->Mysql = new Mysql($this->getConnection());
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Articles, $this->Comments, $this->Mysql);
    }

    /**
     * Test for `getTables()` method
     * @test
     */
    public function testGetTables()
    {
        $this->loadAllFixtures();

        $this->assertEquals(['articles', 'comments'], $this->Mysql->getTables());
    }

    /**
     * Test for `truncateTables()` method
     * @test
     */
    public function testTruncateTables()
    {
        $this->loadAllFixtures();

        $this->assertGreaterThan(0, $this->Articles->find()->count());
        $this->assertGreaterThan(0, $this->Comments->find()->count());

        $this->Mysql->truncateTables();

        $this->assertEquals(0, $this->Articles->find()->count());
        $this->assertEquals(0, $this->Comments->find()->count());
    }
}
