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