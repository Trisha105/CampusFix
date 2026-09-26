<?php
/**
 * CampusFix - Database Configuration & Connection
 *
 * Supports:
 *  - Local XAMPP (MySQL, no SSL)
 *  - TiDB Cloud Serverless (SSL required, via environment variables)
 *  - Any MySQL-compatible server via environment variables
 */

// Connection settings — read from environment variables, fall back to local XAMPP defaults
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'campusfix');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_SSL',  getenv('DB_SSL')  === 'true');

/**
 * Returns a shared PDO instance for database operations.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME
             . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Enable SSL for TiDB Cloud Serverless or any cloud MySQL (DB_SSL=true)
        if (DB_SSL) {
            // Use the system CA bundle (available in Debian/Ubuntu Docker images)
            $caBundle = '/etc/ssl/certs/ca-certificates.crt';
            if (file_exists($caBundle)) {
                $options[PDO::MYSQL_ATTR_SSL_CA]               = $caBundle;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            } else {
                // Fallback: skip cert verification (less secure but still encrypted)
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the error internally, never expose credentials or stack traces
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(503);
            die('<h2 style="font-family:sans-serif;color:#c00;padding:2rem;">
                    Service Temporarily Unavailable
                </h2>
                <p style="font-family:sans-serif;padding:0 2rem;">
                    Unable to connect to the database. Please try again later.
                </p>');
        }
    }

    return $pdo;
}
