# How to use commands

The plugin provides some commands for database backups:

```bash
DatabaseBackup:
 - export
 - import
```

- [Commands](#commands)
    * [export](#export)
    * [import](#import)

## Commands

### export
```bash
$ bin/cake database_backup.export -h
Exports a database backup

Usage:
cake database_backup.export [options]

Options:

--compression, -c  Compression type. By default, no compression will be
                   used (choices: gzip|bzip2)
--connection       Name of the alternative connection to use, for
                   example if you are not using the default connection
--filename, -f     Filename. It can be an absolute path and may contain
                   patterns. The compression type will be automatically
                   set. Filenames can be relative to
                   /path/to/your/app (root of
                   your app) or
                   /path/to/your/app/backups
                   (default target directory).
--help, -h         Display this help.
--quiet, -q        Enable quiet output.
--timeout, -t      Timeout for shell commands
--verbose, -v      Enable verbose output.
```
Example:
```bash
$ bin/cake database_backup.export -c gzip -v
Backup `backups/backup_my_database_20250529113159.sql` has been exported
```

### import
```bash
$ bin/cake database_backup.import -h
Imports a database backup

Usage:
cake database_backup.import [--connection] [-h] [-q] [-t] [-v] <filename>

Options:

--connection      Name of the alternative connection to use, for example
                  if you are not using the default connection
--help, -h        Display this help.
--quiet, -q       Enable quiet output.
--timeout, -t     Timeout for shell commands
--verbose, -v     Enable verbose output.

Arguments:

filename  Filename. It can be an absolute path. Filenames can be
          relative to /path/to/your/app (root
          of your app) or
          /path/to/your/app/backups (default
          target directory).
```
Example:
```bash
$ bin/cake database_backup.import backups/backup_my_database_20250529113159.sql -v
Backup `backups/backup_my_database_20250529113159.sql` has been imported
```