<?php
declare(strict_types=1);

namespace DatabaseBackup;

use ValueError;

/**
 * Compression.
 */
enum Compression: string
{
    case None = 'sql';

    case Gzip = 'sql.gz';

    case Bzip2 = 'sql.bz2';

    /**
     * Returns the matching `Compression` starting from a filename.
     *
     * @param string $filename
     * @return self
     * @throws \ValueError With a filename that does not match any supported compression
     */
    public static function fromFilename(string $filename): self
    {
        foreach (self::cases() as $Compression) {
            if (str_ends_with(haystack: $filename, needle: '.' . $Compression->value)) {
                return $Compression;
            }
        }

        throw new ValueError(sprintf('No valid `%s` value was found for filename `%s`', self::class, $filename));
    }
}
