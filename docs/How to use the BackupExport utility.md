# How to use the BackupExport utility

- [Basic operation](#basic-operation)
- [Optional methods](#optional-methods)
    * [compression()](#compression--)
    * [filename()](#filename--)
    * [timeout()](#timeout--)
- [Export the database](#export-the-database)
    * [export()](#export--)

## Basic operation
To export a database, you have to follow three steps:

**1) Instantiate the class**  
Example:

```php
$BackupExport = new BackupExport();
```

By default, the plugin will always use the default connection set by your app.

The `BackupExport` constructor can however accept as an optional argument a connection, either as a string (connection name) or as an instance of `ConnectionInterface`, if you need to use a connection that is not the default one.

**2) Call one (or more) optional methods**  
Example:
```php
$BackupExport = new BackupExport();
$BackupExport->compression('gzip');
```
Note that all these methods are **optional**.  
See the [methods list](#optional-methods) below.

**3) Export the database, calling the `export()` method**  
Example:
```php
$BackupExport = new BackupExport();
$BackupExport->compression('gzip');
$filename = $BackupExport->export();
```
Note that the `export()` method returns the path of the backup (as string) or `false`.  
See [how to use the `export()` method](#export) below.

## Optional methods
You can call one (or more) optional methods.

### compression()
```php
compression(\DatabaseBackup\Compression|string|null $compression)
```
Sets the compression type.

It can take a case of `DatabaseBackup\Compression` or the compression name as a string (`gzip` or `bzip2`).

Examples:
```php
use DatabaseBackup\Compression;

// ...

$BackupExport->compression(Compression::Gzip);
```
or:
```php
$BackupExport->compression('gzip');
```
With the values `Compression::none` or `null` no compression will be used (so a simple `.sql` file). This is the default value.

### filename()
```php
filename(string $filename)
```

Sets the filename.

Note that using this method, the compression type will be automatically setted by the filename.

The filename can accept some patterns that will make it dynamic.

Patterns are: `{$DATABASE}` (database name), `{$DATETIME}` (datetime), `{$HOSTNAME}` (hostname) and `{$TIMESTAMP}` (timestamp).

For example, with (database name is `my_database`):
```php
$BackupExport->filename('backup_{$DATABASE}_{$DATETIME}.sql');
```
will set the filename as `backup_my_database_20250528111000.sql`.

The filename can be an absolute path or a relative path. Relative paths will be considered relative to the default directory set with the `DatabaseBackup.target` configuration.

### timeout()
```php
timeout(int $timeout)
```

It sets the timeout (in seconds) to use for the export process.

If not set, the default timeout value defined by the `DatabaseBackup.processTimeout` configuration value will be used by default. By calling this method, you can override the value for the current instance.

This is useful when working with very large databases that take a long time to export and import.

## Export the database
### export()
```php
export()
```
Exports the database and returns its filename path on success or `false` if the `Backup.beforeExport` event is stopped.
