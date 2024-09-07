<?php 
namespace Database;

use mysqli;
use Exception;

final class Connection {
    private static $serverName;
    private static $username;
    private static $password;
    private static $database;
    private static $port;
    private static $socket;
    private static $connection;
    private static $lastUsedTime;
    private static $timeout = 600; // Timeout in seconds (10 minutes)

    public function __construct(String $sName, String $uName, String $pass, String $db = null, int $port = null, $soc = null) {
        self::$serverName = $sName;
        self::$username = $uName;
        self::$password = $pass;
        self::$database = $db;
        self::$port = $port;
        self::$socket = $soc;
    }

    public function __destruct() {
        self::close_connection();
    }

    public static function create_connection() {
        self::check_connection_timeout();
        if (self::$connection === null) {
            try {
                self::$connection = new mysqli(self::$serverName, self::$username, self::$password, self::$database, self::$port, self::$socket);
                if (self::$connection->connect_errno > 0) {
                    throw new Exception(self::$connection->connect_error);
                }
                self::$lastUsedTime = time();
                //echo "<p style='color:#0f0;'>Database connected</p>";
            } catch(Exception $e) {
                die("<p style='color:#f00;'><b>Database Connection Failed:</b> " . $e->getMessage() . "</p>");
            }
        }
        return self::$connection;
    }

    public static function close_connection() {
        if (self::$connection) {
            self::$connection->close();
            self::$connection = null;
            self::$lastUsedTime = null;
        }
    }

    private static function check_connection_timeout() {
        if (self::$connection && (time() - self::$lastUsedTime > self::$timeout)) {
            self::close_connection();
        }
    }
}
