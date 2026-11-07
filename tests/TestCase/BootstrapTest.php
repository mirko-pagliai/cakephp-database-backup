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

use Cake\Core\Configure;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * BootstrapTest.
 */
#[CoversNothing]
class BootstrapTest extends TestCase
{
    /**
     * Loads the `config/bootstrap.php` file again.
     *
     * @return void
     */
    protected function loadBootstrap(): void
    {
        //The closure keeps the variables of the file out of the scope of the test
        (static function (): void {
            require ROOT . 'config' . DS . 'bootstrap.php';
        })();
    }

    /**
     * Tests that the default values are set for everything that is not already configured.
     */
    #[Test]
    public function testDefaultValues(): void
    {
        $target = Configure::read('DatabaseBackup.target');
        $expectedConfig = [
            'target' => $target,
            'processTimeout' => 60,
            'Mysql' => [
                'export' => '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}"',
                'import' => '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" "${:DB_NAME}"',
            ],
            'Mariadb' => [
                'export' => '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}"',
                'import' => '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" "${:DB_NAME}"',
            ],
            'Postgres' => [
                'export' => '"${:BINARY}" --format=c -b --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
                'import' => '"${:BINARY}" --format=c -c -e --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
            ],
            'Sqlite' => [
                'export' => '"${:BINARY}" "${:DB_NAME}" .dump',
                'import' => '"${:BINARY}" "${:DB_NAME}"',
            ],
        ];

        Configure::delete('DatabaseBackup');
        //The `target` is kept, since the bootstrap would create the default directory
        Configure::write('DatabaseBackup.target', $target);

        $this->loadBootstrap();

        $this->assertSame($expectedConfig, Configure::read('DatabaseBackup'));
    }

    /**
     * Tests that the values already configured are not overwritten by the default ones.
     */
    #[Test]
    public function testCustomValuesAreKept(): void
    {
        Configure::write('DatabaseBackup.processTimeout', 90);
        Configure::write('DatabaseBackup.Mysql.export', 'custom export');

        $this->loadBootstrap();

        $this->assertSame(90, Configure::read('DatabaseBackup.processTimeout'));
        $this->assertSame('custom export', Configure::read('DatabaseBackup.Mysql.export'));
    }

    /**
     * Tests that, if the `Mariadb` commands are not set, the customized `Mysql` ones are used for them as well.
     *
     * @todo To be removed in the `3.2.x` branch, together with the fallback.
     */
    #[Test]
    #[TestWith(['export'])]
    #[TestWith(['import'])]
    public function testMysqlCommandIsUsedForMariadbIfNotSet(string $operation): void
    {
        Configure::delete("DatabaseBackup.Mariadb.{$operation}");
        Configure::write("DatabaseBackup.Mysql.{$operation}", 'custom mysql command');

        $this->loadBootstrap();

        $result = Configure::read("DatabaseBackup.Mariadb.{$operation}");
        $this->assertSame('custom mysql command', $result);
    }

    /**
     * Tests that the `Mariadb` commands already configured are not overwritten by the `Mysql` ones.
     *
     * @todo To be removed in the `3.2.x` branch, together with the fallback.
     */
    #[Test]
    #[TestWith(['export'])]
    #[TestWith(['import'])]
    public function testMariadbCommandIsNotOverwrittenByMysqlOne(string $operation): void
    {
        Configure::write("DatabaseBackup.Mariadb.{$operation}", 'custom mariadb command');
        Configure::write("DatabaseBackup.Mysql.{$operation}", 'custom mysql command');

        $this->loadBootstrap();

        $result = Configure::read("DatabaseBackup.Mariadb.{$operation}");
        $this->assertSame('custom mariadb command', $result);
    }
}
