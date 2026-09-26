<?php
/**
 * CampusFix - Database Configuration & Connection
 *
 * Uses PHP Data Objects (PDO) for secure, prepared SQL interactions.
 */

// Centralized database connection settings (supports cloud environment variables with local fallbacks)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'campusfix');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

/**
 * Returns a shared PDO instance for database operations.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log technical error on server side without leaking credentials or stack trace to client
            error_log('Database Connection Error: ' . $e->getMessage());

            // Display a user-friendly error message
            die('<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 24px; background: #fff3cd; color: #664d03; border: 1px solid #ffe69c; border-radius: 8px; max-width: 600px; margin: 50px auto; line-height: 1.5;">
                <h3 style="margin-top:0; color: #842029;">Database Connection Error</h3>
                <p>Unable to connect to the CampusFix database. Please verify that MySQL is running in XAMPP and the database <strong>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</strong> exists.</p>
                <p style="margin-bottom:0; font-size: 0.9em; color: #6c757d;">Check that host <code>' . htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8') . '</code> and port <code>' . htmlspecialchars(DB_PORT, ENT_QUOTES, 'UTF-8') . '</code> match your XAMPP configuration.</p>
            </div>');
        }
    }

    return $pdo;
}
