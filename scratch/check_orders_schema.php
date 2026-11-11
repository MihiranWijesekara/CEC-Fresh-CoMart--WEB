<?php
require_once __DIR__ . '/../connection.php';
$rs = Database::search('SHOW CREATE TABLE orders')->fetch_assoc();
echo $rs['Create Table'] . "\n";

