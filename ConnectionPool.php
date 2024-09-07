<?php
namespace Database;

use mysqli;
use Exception;

final class ConnectionPool {
    private static $serverName;
    private static $username;
    private static $password;
    private static $database;
    private static $port;
    private static $socket;
    private static $pool = [];
    private static $maxPoolSize = 10;
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
        foreach (self::$pool as $connection) {
            $connection['conn']->close();
        }
        self::$pool = [];
    }

    public static function get_connection() {
        self::remove_timed_out_connections();

        if (count(self::$pool) > 0) {
            $connection = array_pop(self::$pool);
            if (self::is_connection_alive($connection['conn'])) {
                $connection['last_used'] = time();
                // Log reusing existing connection
                //error_log("Reusing existing connection.");
                return $connection['conn'];
            }
        }

        if (count(self::$pool) < self::$maxPoolSize) {
            try {
                $conn = new mysqli(self::$serverName, self::$username, self::$password, self::$database, self::$port, self::$socket);
                if ($conn->connect_errno > 0) {
                    throw new Exception($conn->connect_error);
                }
                return $conn;
            } catch (Exception $e) {
                // Log the error message
                error_log("Database Connection Failed: " . $e->getMessage());
                throw new Exception("Database Connection Failed: " . $e->getMessage());
            }
        }

        throw new Exception("Connection pool limit reached.");
    }

    public static function release_connection($conn) {
        if (count(self::$pool) < self::$maxPoolSize) {
            self::$pool[] = ['conn' => $conn, 'last_used' => time()];
            // Log releasing connection back to pool
            //error_log("Releasing connection back to pool.");
        } else {
            $conn->close();
            // Log closing connection
            //error_log("Closing connection as pool is full.");
        }
    }

    private static function remove_timed_out_connections() {
        $now = time();
        self::$pool = array_filter(self::$pool, function ($connection) use ($now) {
            if (($now - $connection['last_used']) > self::$timeout) {
                $connection['conn']->close();
                // Log removing timed out connection
                //error_log("Removing timed out connection.");
                return false;
            }
            return true;
        });
    }

    private static function is_connection_alive($conn) {
        return $conn->ping();
    }
}
