# 2.x branch
## 2.14 branch
### 2.14.0
* `DeleteAllCommand` and `RotateCommand` classes had been deprecated and have been removed;
* all classes, methods and code related to sending backups via email had been deprecated, and now they have been
  removed. So, the `BackupManager::send()` method (and, consequently, also the internal
  `BackupManager::getEmailInstance()` method), the `BackupExport::send()` method, the `SendCommand` class and the
  `send` option for the `ExportCommand` have been removed.

## 2.13 branch
### 2.13.5
* added new `DatabaseBackup\Compression` enum, with some methods useful for the complete management of compressions;
* the `BackupExport::compression()` method now accepts a `Compression` value as its `$compression` argument. String and
  `null` values are still supported, but are now deprecated and will be removed in a future release. Additionally, if an
  invalid string is now passed as an argument, a `InvalidArgumentException` exception is thrown;
* the `BackupExport` class no longer directly handles the backup extension, which is automatically deduced from the
  value of `Compression`, now set by default to `Compression::None` (no compression) and which can always be changed
  with the `compression()` and (indirectly) `filename()` methods. For this reason, the `BackupExport::$extension`
  property no longer exists;
* the `RotateCommand` class is deprecated and will be removed in a later release. For this reason, the `ExportCommand`
  class now uses the `BackupManager::rotate()` method to continue supporting the `--rotate` option;
* the `BackupTrait::getValidCompressions()` method is deprecated. Will be removed in a future release;
* compatibility with the transition from `_cake_core_` to `_cake_translations_` expected in CakePHP 5.1;
* the `BackupExport::$defaultExtension` property no longer exists (by now it had become useless);
* updated for the latest version of psalm.

### 2.13.4
* fixed [bug #119](https://github.com/mirko-pagliai/cakephp-database-backup/issues/119): `BackupManager` ignored the  timezone of backup files, and consequently also `IndexCommand`;
* fixed [bug #111](https://github.com/mirko-pagliai/cakephp-database-backup/issues/111): for Mysql it first looks for
  `mariadb` and `mariadb-dump` executables, otherwise `mysql` and `mysqldump` executables;
* all classes, methods and code related to sending backups via email are now deprecated. So, the `BackupManager::send()`
  method (and, consequently, also the internal `BackupManager::getEmailInstance()` method), the `BackupExport::send()`
  method, the `SendCommand` class and the `send` option for the `ExportCommand` are deprecated. All of these will be
  removed in a later release. No replacement is provided;
* setting the `DatabaseBackup.mailSender` value of the configuration is deprecated (bootstrap checks that the value
  has not been set by the user);
* the `DeleteAllCommand` is deprecated. Will be removed in a future release;
* added tests for php 8.4;
* all chainable methods of `BackupExport` and `BackupImport` classes now have the typehint for returning self. Updated 
  descriptions;
* updated `phpunit` to `^10.5.5 || ^11.1.3`;
* updated `psalm` to `6.x`;
* uses `cakedc/cakephp-phpstan`;
* the old `FrozenTime` classes have been replaced with `DateTime` (which it was an alias for);
* extensive revision of descriptions and tags of all classes and methods;
* removed some errors related to phpcs, phpstan and psalm, previously silenced;
* the `README` file has been updated for compatibility with older versions of CakePHP and PHP (branches have been
    removed and older versions are available as tags);
* overall updated `README` file, updated links to CakePHP documentation. Some information has been moved from the
  `README` file to the (new) [Common issues](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Common-issues) wiki page.

### 2.13.3
* added `--reverse` option for the `IndexCommand` ([issue #96](https://github.com/mirko-pagliai/cakephp-database-backup/issues/96));
* the `BackupTrait::getAbsolutePath()` method is now able to recognize a path relative to its `ROOT`, so as to be able
  to take advantage of the autocompletion already offered by the bash console when, for example, you use the `import`
  command from the `ROOT` and the backup directory is inside it;
* fixed a bug for `IndexCommand`, data was not sorted correctly on individual rows. Improved testing;
* slightly improved backup file sorting for `BackupManager::index()` method (this is useful when you have a lot of files);
* requires at least `symfony/process` `7.1.7`, due to this [security vulnerability](https://github.com/mirko-pagliai/cakephp-database-backup/security/dependabot/1);
* fixed some errors in localizations of some strings;
* replaced deprecated `getMockForAbstractClass()` method in tests.

### 2.13.2
* no longer needs `php-tools`;
* removed useless `CommandTestCase`;
* little fixes and updates.

### 2.13.1
* updated for `php-tools` 1.10.0.

### 2.13.0
* requires at least PHP 8.1, PHPUnit 10 and CakePHP 5.0;
* added tests for PHP 8.3.

## 2.12 branch
### 2.12.3
* updated for `php-tools` 1.8.

### 2.12.2
* improved and fixed a bug for `ExportCommand` and `ImportCommand`, in handling some exceptions;
* it no longer needs the `me-tools` package. This removes several (useless) dependencies;
* some, possible changes that prepare it for CakePHP 5 and PHPUnit 10 ([issue #97](https://github.com/mirko-pagliai/cakephp-database-backup/issues/97));
* little fixes. Fixed some deprecations for CakePHP 4.5 ([issue #97](https://github.com/mirko-pagliai/cakephp-database-backup/issues/97));
* improved `BackuManager::index()` method, also regarding the correct files sorting. This also solves a small bug for
  the `rotate()` method (which precisely affects `index()`). The `index()` method now returns a collection of arrays (
  and no longer a collection of `Entity`);
* some testing methods that have been missing for a long time have been added;
* the `BackupTrait::getDriverName()` method can no longer be static;
* removed (old and useless) `BaseCommandTestCase` class;
* added tests for PHP 8.2.

### 2.12.1
* fixed a little bug in the `bootstrap.php` file;
* the `Exceptionist` class provided by me-tools is no longer used (in anticipation of an upcoming deprecation).

### 2.12.0
* added `AbstractBackupUtility::timeout()` method, so now `BackupExport`/`BackupImport` utilities have a method to set the
  timeout for shell commands at runtime. Added `--timeout` option (short: `-t`) for `ExportCommand`/`ImportCommand`;
* the events (`Backup.beforeExport`, `Backup.afterExport`, `Backup.beforeImport`, `Backup.afterImport`, which remain
  implemented by the driver classes) are directly dispatched by the `BackupExport::export()` and `BackupImport::import()`
  methods, and no longer by the drivers themselves;
* added the `AbstractBackupUtility` abstract class that provides the code common to `BackupExport` and `BackupImport`,
  with the new `AbstractBackupUtility::__get()` magic method for reading `BackupExport`/`BackupImport` properties;
* removed `$Driver` public property for `BackupExport`/`BackupImport` and added `AbstractBackupUtility::getDriver()` method;
* the abstract `Driver` class has become `AbstractDriver` and no longer takes a connection as constructor argument, but
  directly uses the one set by the configuration. The old `Driver::_exec()` method has been moved and has become
  `AbstractBackupUtility::getProcess()`. The old `Driver::export()` and `Driver::import()` methods no longer exist and 
  their code has been "absorbed" into the `BackupExport::export()` and `BackupImport::import()` methods;
* `BackupTrait::getDriver()` method has become `AbstractBackupUtility::getDriver()`;
* `BackupTrait::getDriverName()` and `AbstractBackupUtility::getDriver()` no longer accept a connection as argument, but 
  directly use the one set by the configuration;
* the `BackupExport::export()` and `BackupImport::import()` methods can return the filename path on success or `false`
  if the `Backup.beforeExport`/`Backup.beforeImport` events are stopped;
* `Driver::_getExecutable()`, `Driver::_getExportExecutable()` and `Driver::_getImportExecutable()` have become 
  `Driver::getExecutable()`, `Driver::getExportExecutable()` and `Driver::getImportExecutable()`;
* the `Driver::getConfig()` method no longer accepts `null` as argument, but only a string as key, since there is no
  need to return the whole configuration;
* `MySql::getAuthFile()` method has become `getAuthFilePath()`, to be more understandable;
* `MySql::deleteAuthFile()` method returns void (there is no need for it to return anything);
* removed useless `TestCase::getMockForAbstractDriver()` method;
* removed useless `BackupExport::$config` property;
* improved the `ExportCommand` class;
* completely improved the `BackupImportTest` tests.

## 2.11 branch
### 2.11.1
* added the `DatabaseBackup.processTimeout` configuration, which allows you to set a timeout for commands that will be 
  executed in sub-processes (which by default is 60 seconds) and which can be useful for exporting/importing large
  databases (see [issue #88](https://github.com/mirko-pagliai/cakephp-database-backup/issues/88)). Any options to change
  this timeout from `ImportCommand`/`ExportCommand` will be implemented later;
* guaranteed to work with all versions of CakePHP 4;
* added all property types to all classes;
* upgraded to the new fixture system;
* updated for `php-tools` 1.7.4;
* tests have been made compatible with Xampp on Windows;
* many, small improvements to the code and tests, also suggested by PhpStorm.

### 2.11.0
* requires at least PHP 7.4;
* added `MySql::getAuthFile()` method. So the `MySql::$auth` property is now private;
* `createBackup()` and `createSomeBackups()` are now testing global functions and no longer methods provided by the
  `TestCase` class;
* added `CommandTestCase` to test commands;
* many, small tweaks to code and descriptions.

## 2.10 branch
### 2.10.2
* added tests for PHP 8.1;
* little fixes for phpstan, psalm and for the composer.json file.

### 2.10.1
* stable version;
* updated for `php-tools` 1.5.8.

### 2.10.0-beta1
* now allows to configure and customize via bootstrap the executable commands to
    import and export databases, for each driver, with placeholders;
* `__exportExecutableWithCompression()` and `_importExecutableWithCompression()`
    methods provided by the `Driver` class have been removed and incorporated
    into the new `_getExportExecutable()` and `_getImportExecutable()`;
* `BackupTrait::$validExtensions` has been removed and replaced by the
    `DATABASE_BACKUP_EXTENSIONS` constant;
* postgres and sqlite commands are also properly escaped;
* many little fixes and many code simplifications.

## 2.9 branch
### 2.9.2
* added `BackupTrait::getDriverName()` static method; `getConnection()` and
    `getDriver()` methods are now static;
* backtrack (compared to version `2.9.0`): all tracks are auto-discovered,
    otherwise it would not be possible to change the connection you want to work
    on on the fly;
* fixed some tests that produced false positives.

### 2.9.1
* all shell arguments are now correctly escaped.

### 2.9.0
* now uses `symfony/process` to execute import and export shell commands. This
    also allows for better handling of errors reported in the shell. The
    `DatabaseBackup.redirectStderrToDevNull` config key has been removed;
* only the binaries needed for the database driver used are auto-discovered;
* tests are now only run for one driver at a time, by default `mysql`. You can
    choose another driver by setting `driver_test` or ``db_dsn` environment
    variables before running `phpunit`;
* migration to github actions.

## 2.8 branch
### 2.8.7
* fixed a small bug when using the numeric hostname (`127.0.0.1` or `::1`) as
    the automatic backup filename;
* export and import error messages are now more specific;
* `BackupExport` returns a more accurate error in case of invalid filename;
* added `Driver::_exec()` method;
* tests no longer fail with `DatabaseBackup.redirectStderrToDevNull` set to `false`.

### 2.8.6
* fixed bootstrap, `mkdir` errors are no longer suppressed;
* extensive improvement of function descriptions and tags. The level of `phpstan`
    has been raised.

### 2.8.5
* ready for php `8.0`;
* extensive improvement of function descriptions and tags.

### 2.8.4
* `BackupManager::delete()` returns the full path;
* all methods provided by `BackupManager` can now be called statically, except
    for the `send()` method;
* extensive improvement of function descriptions and tags;
* ready for `phpunit` 9.

### 2.8.3
* updated for `php-tools` 1.4.5;
* added `phpstan`, so fixed some code.

### 2.8.2
* updated for `php-tools` 1.4.1.

### 2.8.1
* fixed I18n translations;
* fixed [bug for `Command` class](https://github.com/mirko-pagliai/cakephp-database-backup/pull/54).

### 2.8.0
* updated for `cakephp` 4 and `phpunit` 8.

## 2.7 branch
### 2.7.1
* fixed [bug for `Command` class](https://github.com/mirko-pagliai/cakephp-database-backup/pull/54).

### 2.7.0
* `BackupTrait::getBinary()` method has been moved to `Driver` abstract class;
* `BackupTrait::getTarget()`, `BackupTrait::getDriverName()` and
    `BackupTrait::getValidExtensions()` methods have been removed.

## 2.6 branch
### 2.6.6
* tests have been optimized and speeded up;
* APIs are now generated by `phpDocumentor` and no longer by` apigen`.

### 2.6.5
* little fixes.

### 2.6.4
* little fixes for `BackupManager` and `BackupExport` classes;
* added tests for lower dependencies;
* improved exception message when no binary file is found;
* no longer uses the `Folder` class.

### 2.6.3
* little fixes.

### 2.6.2
* added `BackupTrait::getDriverName()` method;
* `BackupExport::compression()` takes a compression type name as string or
    `null` to disable compression;
* `BackupExport::send()` takes a recipient's email address as string or `null`
    to disable sending backup;
* `BackupTrait::getCompression()` returns `null` with no compression;
* the `DriverTestCase` class now implements `testExportOnFailure()` and
    `testImportOnFailure()` test methods;
* improved printing of the backup table for the `IndexCommand`;
* updated for `php-tools` 1.2 and `me-tools` 2.18.7.
* added [API](//mirko-pagliai.github.io/cakephp-database-backup).

### 2.6.1
* added `DriverTestCase::getMockForDriver()` method;
* `DriverTestCase::allRecords()` method renamed as `getAllRecords()`;
* many small code fixes;
* requires `me-tools` package for dev;
* removed `ConsoleIntegrationTestTrait`, because it is now sufficient to use the
    same trait provided by `me-tools`;
* updated for `php-tools` 1.1.12.

### 2.6.0
* `BackupShell` has been replaced with console commands. Every method of the
    previous class is now a `Command` class;
* `BackupManager::index()` returns a collection of backups;
* `ConsoleIntegrationTestCase` has been replaced by `ConsoleIntegrationTestTrait`.
    `TestCaseTrait` has been removed and its methods moved to `TestCase`;
* removed `DATABASE_BACKUP` constant;
* updated for CakePHP 3.7.

## 2.5 branch
### 2.5.1
* updated for CakePHP 3.6 and 3.7. Added `Plugin` class;
* many small code fixes.

### 2.5.0
* now it uses the `mirko-pagliai/php-tools` package. This also replaces
    `mirko-pagliai/reflection`;
* removed `BackupTrait::getClassShortName()` method. The
    `get_class_short_name()` global function will be used instead.

## 2.4 branch
### 2.4.0
* fixed bug trying to email a nonexistent backup;
* `VALID_COMPRESSIONS` and `VALID_EXTENSIONS` constants have been replaced by
    `getValidCompressions()` and `getValidExtensions()` methods provided by the
    `BackupTrait` class;
* replaced `InternalErrorException` with `InvalidArgumentException` and
    `RuntimeException`. This allows compatibility with CakePHP 3.6 branch.

## 2.3 branch
### 2.3.0
* full support for working under Windows;
* added `Driver::getConfig()` method, removed `Driver::$config` property. This
    allows you to get the configuration values safely;
* fixed a bug for export and import executables with Postgres databases;
* before importing a sqlite backup, each table is dropped, rather than deleting
    the database file;
* added `isWin()` global function;
* tests can have `onlyUnix` or `onlyWindows` group.

## 2.2 branch
### 2.2.0
* added `ConsoleIntegrationTestCase` and `TestCaseTrait` classes. Console tests
    have been simplified;
* updated for CakePHP 3.5.

## 2.1 branch
### 2.1.4
* when a backup is sent by mail, the mimetype is forced;
* fixed some tests.

### 2.1.3
* added `createBackup()` and `createSomeBackups()` to the `TestCase` class;
* `BackupManager::_send()` has become `getEmailInstance()`.

### 2.1.2
* fixed `composer.json`: the plugin requires at least version 3.4 of CakePHP.

### 2.1.1
* `afterExport()`, `afterImport()`, `beforeExport()` and `beforeImport` methods
    are now real events;
* now you can choose if you want to redirects stderr to `/dev/null`. This
    suppresses the output of executed commands.

### 2.1.0
* added support for Postgres databases;
* all `export()` and `import()` methods have been moved to the `Driver` class;
* added `afterExport()`, `afterImport()`, `beforeExport()` and `beforeImport`
    methods to the `Driver` class;
* `getCompression()` and `getExtension()` moved from `Driver` to `BackupTrait`
    class, because these methods are not strictly related to the database engine
    you are using;
* removed `getValidExtensions()` and `getValidCompressions()` methods from
    `Driver` class, because extensions and compressions are the same for any
    database engine;
* removed `getDefaultExtension()` method from `Driver` class, because the
    default extension is the same for any database engine.

## 2.0 branch
### 2.0.0
* the plugin has been renamed as `DatabaseBackup` (`cakephp-database-backup`);
* the code has been completely rewritten to work with drivers, so it can also
    work with other database engines;
* added support for Sqlite database;
* checks the return status code when it runs `mysql` and `mysqldump` from the
    command line.

# 1.x branch
## 1.1 branch
### 1.1.1
* fixed bugs in the table output for the `BackupShell::index()` method;
* `_storeAuth()` methods from `BackupExport` and `BackupImport` classes are
    now private;
* added `BackupTrait::getConnection()` method;
* added `BackupTrait::getValidCompressions()` method.

### 1.1.0
* added `BackupShell::send()` and `BackupManager::send()` methods to send backup
    files via mail;
* added `--send` option to `BackupShell::export()`;
* added `BackupExport::send()` to set up the email recipient, so by calling the
    `export()` method the backup file will be sent via email;
* added `BackupTrait`,  that provides some methods used by all other classes;
* added `rtr()` (relative to root) global function. This simplifies the output
    of some methods provided by `BackupShell`;
* global functions `compressionFromFile()`, `extensionFromCompression()` and
    `extensionFromFile()` have been replaced with the `getCompression()` and
    `getExtension()` methods provided by the `BackupTrait`;
* all methods belonging to the `BackupManager` class are no longer static.

## 1.0 branch
### 1.0.3
* the target directory is created automatically, if it does not exist;
* `BackupManager::index()` returns an array of entities;
* added `MYSQL_BACKUP` constant.

### 1.0.2
* methods that have been deprecated with CakePHP 3.4 have been replaced;
* updated for CakePHP 3.4.

### 1.0.1
* added `BackupManager::deleteAll()` and `BackupShell::deleteAll()` methods;
* improved tests. Also errors in the shell are checked.

### 1.0.0
* first release.
