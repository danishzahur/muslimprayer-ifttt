<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(660);

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/utility/Db.php');

header('Content-Type: text/plain');
header('Content-Disposition: filename="ifttt-dump.sql"');

$dbConnection = new Db();
$rows = $dbConnection->fetchAll(
    'SELECT trigger_identity, next_notification FROM prayer_time WHERE next_notification > '. strtotime('-5 day')
);

if (count($rows)) {
    echo '# '. date('c') ."\n\n";
    readfile(__DIR__ . '/utility/table.sql');
    echo "\n\n";

    $i = $rowLast = 0;
    foreach ($rows as $row) {
        
        if ($i % 10 == 0) {
            if ($i > 0) {
                echo ");\n";
            }
            echo 'INSERT INTO "prayer_time" VALUES ('. ($i / 10 + 1);
            $rowLast = $i + 10;
        }
        
        echo ", '". $row->trigger_identity ."', ". $row->next_notification;

        $i++;
    }

    for (; $i < $rowLast; $i++) {
        echo ", '', 0";
    }
    echo ");\n\n";

    echo 'ALTER SEQUENCE "prayer_time_id_seq" RESTART WITH '. ($i / 10 + 1) .";\n";
}