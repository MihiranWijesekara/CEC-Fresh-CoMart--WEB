<?php
require_once __DIR__ . '/../connection.php';
$res = Database::search('SHOW COLUMNS FROM users');
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Query error: " . Database::$connection->error . "\n";
}

