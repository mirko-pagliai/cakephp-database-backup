<?php
declare(strict_types=1);

namespace DatabaseBackup;

use ValueError;

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
     * @since 2.15.0
     */
    public static function validCases(): array
    {
        return array_filter(
            array: Compression::cases(),
            callback: fn (Compression $Compression): bool => $Compression->isValid(),
        );
    }

    /**
     * Returns `true` if the current one is a valid `Compression`.
     *
     * This is equivalent to saying that it is different from `Compression::None`.
     *
     * @return bool
     * @since 2.15.0
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
            throw new ValueError(sprintf('No valid `%s` value was found for filename `%s`', self::class, $filename));
        }

        return $Compression;
    }

    /**
     * Tries to return the matching `Compression` starting from a filename.
     *
     * @param string $filename
     * @return self|null `Compression` or `null` if filename does not match a supported compression.
     */
    public static function tryFromFilename(string $filename): ?self
    {
        $filename = strtolower($filename);

        foreach (self::cases() as $Compression) {
            if (str_ends_with(haystack: $filename, needle: '.' . $Compression->value)) {
                return $Compression;
            }
        }

        return null;
    }
}
