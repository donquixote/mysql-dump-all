This script will
- Dump all your mysql databases.
- One file per database, gzipped, in `./dump/now/$dbname.sql.gz`.
- Drupal `cache_*` and similar tables are skipped.
- Maintain hourly, daily, monthly, yearly archives of the `./dump/now folder`.  
  E.g. `./dump/weekday/tue/..` contains the latest dump that was made on a tuesday.
- Use symlinks to reduce data duplication in the archives.  
  E.g. if today is tuesday, then `./dump/weekday/tue` will be a symlink to `./dump/now`.
- Rotate those archives.  
  E.g. if today is wednesday, but `./dump/weekday/tue` is found to be a symlink to `./dump/now`, then
  - The symlink at `./dump/weekday/tue` will be deleted.
  - Instead, the folder `./dump/now` will be copied to `./dump/weekday/tue`.
  - This is supposed to happen *before* the dumps in `./dump/now` are updated.


How to use:
- PHP is required.  
  (I find PHP more readable than plain shell script)
- Create a backup db user with pw and readonly privileges on all databases:  
  `GRANT LOCK TABLES, SELECT, SHOW VIEW ON *.* TO 'backup'@'localhost' IDENTIFIED BY '*****';`
- Copy `example.settings.ini` to `settings.ini` and configure it with the db user credentials.
- `php rotate.sh.php | sh` will rotate the archives.
- `php dump-all.sh.php | sh` will dump all tables.
- `sh dump-all.sh` is the combination of rotate and dump-all.  
  This is what you want to put in your cron.
