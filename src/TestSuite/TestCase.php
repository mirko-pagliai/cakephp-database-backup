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
 * @since       2.0.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\TestSuite\TestCase as CakeTestCase;
use Reflection\ReflectionTrait;

/**
 * TestCase class
 */
class TestCase extends CakeTestCase
{
    use ReflectionTrait;
    
    /**
     * Loads all fixtures declared in the `$fixtures` property
     * @return void
     */
    public function loadAllFixtures()
    {
        $fixtures = $this->getProperty($this->fixtureManager, '_fixtureMap');

        call_user_func_array([$this, 'loadFixtures'], array_keys($fixtures));
    }
}
