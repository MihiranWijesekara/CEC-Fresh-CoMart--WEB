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

        public static function search($q){
            Database::setUpConnection();
            $resultset= Database::$connection->query($q);
            return $resultset;
        }
    }
?>