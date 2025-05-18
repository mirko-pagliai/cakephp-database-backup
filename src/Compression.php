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

namespace DatabaseBackup;

use ValueError;
use function Cake\I18n\__d;

/**
 * Compression.
 *
 * @since 2.13.5
 */
enum Compression: string
{
    case None = 'sql';

    case Gzip = 'sql.gz';

    case Bzip2 = 'sql.bz2';

    /**
     * Returns all valid cases.
     *
     * This is equivalent to saying all cases that match the `isValid()` method.
     *
     * @return array<\DatabaseBackup\Compression>
     * @since 3.0.0
     */
    public static function validCases(): array
    {
        return array_filter(
            array: Compression::cases(),
            callback: fn(Compression $Compression): bool => $Compression->isValid(),
        );
    }

    /**
     * Returns `true` if the current one is a valid `Compression`.
     *
     * This is equivalent to saying that it is different from `Compression::None`.
     *
     * @return bool
     * @since 3.0.0
     */
    public function isValid(): bool
    {
        return $this !== Compression::None;
    }

    /**
     * Returns the matching `Compression` starting from a filename.
     *
     * @param string $filename
     * @return self
     * @throws \ValueError With a filename that does not match any supported compression.
     */
    public static function fromFilename(string $filename): self
    {
        $Compression = self::tryFromFilename($filename);
        if (!$Compression) {
            throw new ValueError(__d('database_backup', 'No valid `{0}` value was found for filename `{1}`', self::class, $filename));
        }

        return $Compression;
    }

    /**
     * Tries to return the matching `Compression` starting from a filename.
     *
     * @param string $filename
     * @return self|null `Compression` or `null` if the filename does not match the supported compression.
     */
    public static function tryFromFilename(string $filename): ?self
    {
        return array_find(self::cases(), fn($Compression) => str_ends_with(haystack: strtolower($filename), needle: '.' . $Compression->value));
    }
}
