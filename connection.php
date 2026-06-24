<?php

    class Database{

        public static $connection;

        public static function setUpConnection(){
            if(!isset(Database::$connection)){
                Database::$connection= new mysqli("localhost","root","Ab2#*De#","cec-comart-web","3306");
                
                // Self-healing database migration: add is_admin column to users table if missing
                $check = Database::$connection->query("SHOW COLUMNS FROM `users` LIKE 'is_admin'");
                if ($check && $check->num_rows == 0) {
                    Database::$connection->query("ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0");
                    Database::$connection->query("UPDATE `users` SET `is_admin` = 1 WHERE `email` = 'achintha@gmail.com'");
                }
            }
        }

        public static function iud($q){
            Database::setUpConnection();
            Database::$connection->query($q);
        }

        public static function isBulkItem($catId, $unitStr) {
            $bulkCategories = [1, 2, 14, 15, 16];
            if (!in_array((int)$catId, $bulkCategories)) {
                return false;
            }
            $unitLower = strtolower(trim($unitStr));
            return preg_match('/(kg|g|l|ml)$/i', $unitLower);
        }

        public static function getUnitMetric($unitStr) {
            if (preg_match('/(kg|g|l|ml)$/i', strtolower(trim($unitStr)), $matches)) {
                return $matches[1];
            }
            return 'units';
        }

        public static function parseUnitToGrams($unitStr) {
            $unitStr = strtolower(trim($unitStr));
            if (preg_match('/^([\d\.]+)\s*(kg|g|l|ml)?$/i', $unitStr, $matches)) {
                $val = (float)$matches[1];
                $metric = isset($matches[2]) ? $matches[2] : 'units';
                $baseVal = $val;
                if ($metric === 'kg') {
                    $baseVal = $val * 1000;
                } else if ($metric === 'g') {
                    $baseVal = $val;
                } else if ($metric === 'l') {
                    $baseVal = $val * 1000;
                } else if ($metric === 'ml') {
                    $baseVal = $val;
                }
                return ['val' => $val, 'metric' => $metric, 'baseVal' => $baseVal];
            }
            return ['val' => 1, 'metric' => 'units', 'baseVal' => 1];
        }

        public static function formatQuantity($qty, $unitStr, $catId) {
            $qty = (float)$qty;
            if (!Database::isBulkItem($catId, $unitStr)) {
                return ($qty == (int)$qty ? (int)$qty : number_format($qty, 2));
            }
            $parsed = Database::parseUnitToGrams($unitStr);
            if ($parsed['metric'] === 'units') {
                return ($qty == (int)$qty ? (int)$qty : number_format($qty, 2)) . ' units';
            }
            $totalBase = $qty * $parsed['baseVal'];
            if ($parsed['metric'] === 'kg' || $parsed['metric'] === 'g') {
                if ($totalBase >= 1000) {
                    $val = $totalBase / 1000;
                    return ($val == (int)$val ? (int)$val : number_format($val, 2)) . ' kg';
                } else {
                    return ($totalBase == (int)$totalBase ? (int)$totalBase : number_format($totalBase, 1)) . ' g';
                }
            } else if ($parsed['metric'] === 'l' || $parsed['metric'] === 'ml') {
                if ($totalBase >= 1000) {
                    $val = $totalBase / 1000;
                    return ($val == (int)$val ? (int)$val : number_format($val, 2)) . ' L';
                } else {
                    return ($totalBase == (int)$totalBase ? (int)$totalBase : number_format($totalBase, 1)) . ' ml';
                }
            }
            return ($qty == (int)$qty ? (int)$qty : number_format($qty, 2)) . ' units';
        }

        public static function search($q){
            Database::setUpConnection();
            $resultset= Database::$connection->query($q);
            return $resultset;
        }
    }
?>