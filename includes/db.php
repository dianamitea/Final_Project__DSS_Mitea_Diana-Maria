<?php
/**
 * Reusable database connection file.
 * Returns a MySQLi connection using prepared statements.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'flower_shop_dss');
define('DB_CHARSET', 'utf8mb4');

function getDbConnection(): mysqli
{
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            // Do not expose connection details to the user
            error_log('DB connection failed: ' . $conn->connect_error);
            die('Database connection error. Please try again later.');
        }

        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}
