<?php
//if not accessed directly (this is defined in index) then define the connection class else die.

namespace Database;
use PDO;

if (defined('DIRECT_ACCESS') && DIRECT_ACCESS == false) {
    class Database {

        public $DatabaseObject = null;

        public function getDatabaseObject()
        {

            if ($this->DatabaseObject != null) return $this->DatabaseObject;

            $host = getenv('MYSQL_HOST');
            $username = getenv('MYSQL_USERNAME');
            $dbname = 'tigger';
            $password = getenv('MYSQL_PASSWORD');

            try {
                $this->DatabaseObject = new PDO("mysql:dbname={$dbname};host={$host};charset=utf8", $username, $password);
            } catch (PDOException $ex) {
                die("Geen connectie met database: " . $ex->getMessage());
            }

            $this->DatabaseObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->DatabaseObject->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                function undo_magic_quotes_gpc(&$array)
                {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            undo_magic_quotes_gpc($value);
                        } else {
                            $value = stripslashes($value);
                        }
                    }
                }

                undo_magic_quotes_gpc($_POST);
                undo_magic_quotes_gpc($_GET);
                undo_magic_quotes_gpc($_COOKIE);
            }
            return $this->DatabaseObject;
        }
    }
} else {
    die("Direct access is not allowed");
}

