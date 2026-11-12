<?php
if(!defined('DB_SERVER')){
    require_once(str_replace('\\', '/', dirname(__DIR__)) . '/initialize.php');
}

class DBConnection{

    private $host;
    private $port;
    private $username;
    private $password;
    private $database;
    private $ssl;
    
    public $conn;
    public $error = null;
    
    public function __construct(){
        // Safe PHP 8.x initialization inside constructor
        $this->host = defined('DB_SERVER') ? DB_SERVER : (getenv('DB_SERVER') ?: 'localhost');
        $this->port = defined('DB_PORT') ? (int)DB_PORT : (int)(getenv('DB_PORT') ?: 3306);
        $this->username = defined('DB_USERNAME') ? DB_USERNAME : (getenv('DB_USERNAME') ?: 'root');
        $this->password = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
        $this->database = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'orrs_db');
        $this->ssl = defined('DB_SSL') ? (bool)DB_SSL : (getenv('DB_SSL') !== false ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : ($this->host !== 'localhost' && $this->host !== '127.0.0.1'));

        if (!isset($this->conn) || !$this->conn) {
            // Prevent uncaught mysqli_sql_exception in PHP 8.1+
            @mysqli_report(MYSQLI_REPORT_OFF);

            $mysqli = mysqli_init();
            if (!$mysqli) {
                $this->error = 'mysqli_init failed';
                $this->conn = false;
                return;
            }

            // Connection timeout (10 seconds)
            $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

            try {
                if ($this->ssl) {
                    // Aiven Cloud requires SSL (require_secure_transport=ON)
                    $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
                    $flags = MYSQLI_CLIENT_SSL;
                    $connected = @$mysqli->real_connect($this->host, $this->username, $this->password, $this->database, $this->port, NULL, $flags);
                } else {
                    $connected = @$mysqli->real_connect($this->host, $this->username, $this->password, $this->database, $this->port);
                }

                if (!$connected) {
                    $this->error = mysqli_connect_error();
                    $this->conn = false;
                } else {
                    $mysqli->set_charset("utf8mb4");
                    $this->conn = $mysqli;
                }
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                $this->conn = false;
            }
        }
    }
    
    public function __destruct(){
        if ($this->conn && $this->conn instanceof mysqli) {
            @$this->conn->close();
        }
    }
}
?>