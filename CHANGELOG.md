# 1.x branch
## 1.1 branch
### 1.1.0
* added `BackupShell::send()` and `BackupManager::send()` methods to send backup
    files via mail;
* added `BackupExport::send()` to set up the email recipient, so by calling the
    `export()` method the backup file will be sent via email;
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