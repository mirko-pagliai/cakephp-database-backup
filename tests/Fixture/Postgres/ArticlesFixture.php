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
namespace DatabaseBackup\Test\Fixture\Postgres;

use Cake\Test\Fixture\ArticlesFixture as CakeArticlesFixture;

/**
 * ArticlesFixture class
 */
class ArticlesFixture extends CakeArticlesFixture
{
    /**
     * Fixture datasource
     * @var string
     */
    public $connection = 'test_postgres';
}
