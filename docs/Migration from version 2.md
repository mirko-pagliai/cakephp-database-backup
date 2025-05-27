# Migration from version 2

These instructions allow you to migrate from the 2.x branch to the 3.x branch.

In particular and more precisely, they are intended to be valid when moving from the `2.14.x` or `2.15.x` branch to the `3.0.x` branch.
Therefore, make sure you have first updated, preferably, to one of the latest versions of the `2.14.x` or `2.15.x` branches.

## From `mysql`/`mysql-dump` to `mariadb`/`mariadb-dump`

If you use a MySql/MariaDB database, make sure you have the `mariadb` and `mariadb-dump` executables (just make sure, if present the plugin will detect and use them automatically), which have replaced the old `mysql` and `mysql-dump`.

You can also do a simple shell check using the unix command `where`:

```bash
$ whereis mariadb && whereis mariadb-dump
mariadb: /usr/bin/mariadb /usr/share/man/man1/mariadb.1.gz
mariadb-dump: /usr/bin/mariadb-dump /usr/share/man/man1/mariadb-dump.1.gz
```
