# Database Connection Management in PHP

This README provides an overview of two PHP classes designed to handle efficient MySQL database connections using `mysqli`. The classes provided are:

1. **ConnectionPool**: A connection pool class for reusing connections and managing a pool of database connections.
2. **Connection**: A simpler class for managing a single database connection with automatic timeout handling.

## Class Overview

### 1. `ConnectionPool` Class

The `ConnectionPool` class is a singleton pattern implementation that manages a pool of MySQL database connections. It is designed to improve performance by reusing database connections and minimizing the overhead of creating new connections.

#### Key Features:

- **Connection Pooling**: Maintains a pool of database connections that can be reused.
- **Connection Timeout**: Removes idle connections from the pool after a specified timeout.
- **Max Pool Size**: Limits the number of active connections in the pool.
- **Automatic Resource Management**: Closes all connections on class destruction.

#### Usage:

1. **Initialization**: Instantiate the `ConnectionPool` class with database credentials.
   
   ```php
   use Database\ConnectionPool;

   $pool = new ConnectionPool('localhost', 'username', 'password', 'database', 3306);
   ```

2. **Get a Connection**: Use `get_connection()` to retrieve a database connection from the pool. If a reusable connection is available, it will be returned; otherwise, a new connection is created.

   ```php
   try {
       $conn = ConnectionPool::get_connection();
       // Use the $conn for your database operations
   } catch (Exception $e) {
       echo "Error: " . $e->getMessage();
   }
   ```

3. **Release a Connection**: After using a connection, release it back to the pool using `release_connection()`.

   ```php
   ConnectionPool::release_connection($conn);
   ```

4. **Automatic Cleanup**: When the script ends, the destructor will close all open connections in the pool.

#### Example:

```php
use Database\ConnectionPool;

$pool = new ConnectionPool('localhost', 'username', 'password', 'database');

try {
    $conn = ConnectionPool::get_connection();
    // Perform database operations using $conn
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    ConnectionPool::release_connection($conn);
}
```

#### Best Practice for Creating and Auto-Closing Connections

To ensure efficient management and auto-closing of connections, you can use the following approach:

```php
try {

    $database_server = $GLOBALS['config']['DB_SERVER_NAME'];
    $database_user = $GLOBALS['config']['DB_USERNAME'];
    $database_name = $GLOBALS['config']['DB_NAME'];
    $database_password = $GLOBALS['config']['DB_PASSWORD'];

    // Initialize connection pool (typically done once in application setup)
    new ConnectionPool($database_server, $database_user, $database_password, $database_name);

    // Get a connection from the pool
    $GLOBALS['connection'] = ConnectionPool::get_connection();

    // Register a shutdown function to release the connection when the script ends
    register_shutdown_function(function() {
        if (isset($GLOBALS['connection'])) {
            ConnectionPool::release_connection($GLOBALS['connection']);
        }
    });
} catch (Exception $e) {
    // Handle exception (e.g., connection pool limit reached)
    error_log($e->getMessage());
}
```

In this example:
- The `ConnectionPool` is initialized with global database configuration settings.
- A connection is obtained from the pool and stored in a global variable.
- A shutdown function is registered to automatically release the connection back to the pool when the script terminates, ensuring that connections are not left open accidentally.

### 2. `Connection` Class

The `Connection` class is a simple implementation for managing a single database connection. It provides automatic connection timeout handling and a straightforward way to connect to a database.

#### Key Features:

- **Single Connection Management**: Manages a single database connection.
- **Automatic Timeout Handling**: Closes the connection if it is idle for a specified timeout.
- **Error Handling**: Provides basic error handling for connection failures.

#### Usage:

1. **Initialization**: Instantiate the `Connection` class with database credentials.

   ```php
   use Database\Connection;

   $db = new Connection('localhost', 'username', 'password', 'database', 3306);
   ```

2. **Create a Connection**: Use `create_connection()` to establish a database connection.

   ```php
   $conn = Connection::create_connection();
   ```

3. **Close the Connection**: After using the connection, it can be explicitly closed using `close_connection()`.

   ```php
   Connection::close_connection();
   ```

#### Example:

```php
use Database\Connection;

$db = new Connection('localhost', 'username', 'password', 'database');

$conn = Connection::create_connection();

// Perform database operations using $conn

Connection::close_connection();
```

## Conclusion

Both `ConnectionPool` and `Connection` classes provide efficient ways to manage MySQL database connections in PHP. The `ConnectionPool` class is ideal for applications that require multiple simultaneous database connections and wish to minimize connection overhead. The `Connection` class is suitable for simpler applications that only require a single database connection with automatic timeout handling.

Choose the appropriate class based on your application's requirements to improve database connection management and performance.
