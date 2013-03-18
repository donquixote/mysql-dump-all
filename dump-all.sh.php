<?php

$settings = parse_ini_file('./settings.ini', TRUE);

$user = $settings['db']['user'];
$pass = $settings['db']['pass'];


// Find db names
// -----------------------------------------------------------------------------

$db = new PDO("mysql:host=localhost", $user, $pass);
$q = $db->query('SHOW DATABASES');

$dbnames = array();
while (FALSE !== $dbname = $q->fetchColumn(0)) {
  $dbnames[$dbname] = $dbname;
}
unset($dbnames['information_schema']);

// Tables to exclude
$exclude = array();
foreach ($dbnames as $dbname) {
  $db->query("USE $dbname");
  $q = $db->query('SHOW TABLES');
  $tables = array();
  while (FALSE !== $tablename = $q->fetchColumn(0)) {
    $tables[$tablename] = $tablename;
  }
  $exclude[$dbname] = array();
  if (1
    && isset($tables['variable'])
    && isset($tables['system'])
    && isset($tables['menu_router'])
  ) {
    // Heuristic: This is a Drupal database.
    foreach ($tables as $tablename) {
      if (0
        || 'cache_' === substr($tablename . '_', 0, 6)
        || in_array($tablename, array(
          'watchdog', 'captcha_sessions', 'sessions',
          'search_index', 'search_dataset', 'search_node_links', 'search_total',
        ))
      ) {
        $exclude[$dbname][$tablename] = TRUE;
      }
    }
  }
}


// sh: Dump tables
// -----------------------------------------------------------------------------

$sh[] = '';
if (is_dir("./dump/now")) {
  $sh[] = "rm -r ./dump/now";
}
$sh[] = "mkdir ./dump/now";

foreach ($dbnames as $dbname) {

  // Print a progress message.
  $sh[] = "echo 'db: $dbname'";

  // Ignore cache tables.
  $excl = '';
  foreach ($exclude[$dbname] as $tablename => $true) {
    $excl .= " --ignore-table=$dbname.$tablename";
  }

  // Dump non-cache tables with data.
  $cmd = "mysqldump -u$user -p$pass $dbname $excl";

  // Dump structure of cache tables.
  if (!empty($exclude[$dbname])) {
    $impl = implode(' ', array_keys($exclude[$dbname]));
    $cmd .= " && mysqldump --no-data -u$user -p$pass $dbname $impl";
    $cmd = "($cmd)";
  }

  $sh[] = "$cmd | gzip > ./dump/now/$dbname.sql.gz";
}


// Print shell script
// -----------------------------------------------------------------------------

print "\n" . implode("\n", $sh) . "\n";
