<?php
/**
 * Database configuration for Quiz Game - Real-time High Performance
 * No emojis, production-ready for network-based concurrent access
 */

class Database {
    private static $instance = null;
    private $pdo = null;
    
    // Database credentials
    private $host = 'localhost';
    private $dbname = 'quiz_game';
    private $username = 'root';
    private $password = '';
    
    // Performance tuning flags
    private $persistent = false;  // Set to true only if you understand implications
    private $logErrors = true;    // Log DB errors to file instead of exposing to user
    
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get singleton instance (prevents multiple connections per request)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish PDO connection with real-time optimizations
     */
    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,  // Use native prepared statements (faster, safer)
            PDO::ATTR_STRINGIFY_FETCHES => false, // Keep numeric types as numbers
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Buffer results for faster row counting
            PDO::ATTR_TIMEOUT => 5,               // Connection timeout seconds
        ];
        
        if ($this->persistent) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }
        
        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            // Set timezone to server time for consistency
            $this->pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            $this->handleError($e);
            die('System maintenance: Database temporarily unavailable. Please try again later.');
        }
    }
    
    /**
     * Get the PDO connection object
     */
    public function getConnection() {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }
    
    /**
     * Log errors silently without exposing details to end user
     */
    private function handleError($exception) {
        if ($this->logErrors) {
            $logFile = __DIR__ . '/../logs/db_errors.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $message = date('Y-m-d H:i:s') . ' | ' . $exception->getMessage() . ' | ' . 
                       $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
            file_put_contents($logFile, $message, FILE_APPEND);
        }
    }
    
    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize database singleton');
    }
}

// For backward compatibility, also provide a global $pdo variable
$pdo = Database::getInstance()->getConnection();

// Optional: Helper function for quick queries (real-time leaderboard use)
function db_query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
?>