<?php
/**
 * 1-Click Aiven MySQL Cloud Database Setup & Importer
 * Online Railway Reservation System (ORRS)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/initialize.php');
require_once(__DIR__ . '/classes/DBConnection.php');

$db_host = defined('DB_SERVER') ? DB_SERVER : (getenv('DB_SERVER') ?: 'localhost');
$db_port = defined('DB_PORT') ? (int)DB_PORT : (int)(getenv('DB_PORT') ?: 3306);
$db_user = defined('DB_USERNAME') ? DB_USERNAME : (getenv('DB_USERNAME') ?: 'root');
$db_pass = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$db_name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'orrs_db');
$db_ssl  = defined('DB_SSL') ? (bool)DB_SSL : (getenv('DB_SSL') !== false ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : ($db_host !== 'localhost' && $db_host !== '127.0.0.1'));

$sql_file = __DIR__ . '/database/orrs_db.sql';

$connection_status = null;
$connection_error = null;
$conn = null;
$tables_found = [];
$admin_user = null;
$import_result = null;

// Connect to Database
try {
    $mysqli = mysqli_init();
    if ($mysqli) {
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);
        if ($db_ssl) {
            $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $connected = @$mysqli->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);
        } else {
            $connected = @$mysqli->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
        }

        if ($connected) {
            $conn = $mysqli;
            $conn->set_charset("utf8mb4");
            $connection_status = true;

            // Fetch existing tables
            $t_res = $conn->query("SHOW TABLES");
            if ($t_res) {
                while ($row = $t_res->fetch_array()) {
                    $tname = $row[0];
                    $c_res = $conn->query("SELECT COUNT(*) FROM `{$tname}`");
                    $cnt = $c_res ? $c_res->fetch_array()[0] : 0;
                    $tables_found[$tname] = $cnt;
                }
            }

            // Check admin user
            if (isset($tables_found['users'])) {
                $u_res = $conn->query("SELECT id, username, firstname, lastname, type, status FROM `users` WHERE type = 1 LIMIT 1");
                if ($u_res && $u_res->num_rows > 0) {
                    $admin_user = $u_res->fetch_assoc();
                }
            }
        } else {
            $connection_status = false;
            $connection_error = mysqli_connect_error();
        }
    }
} catch (Exception $e) {
    $connection_status = false;
    $connection_error = $e->getMessage();
}

// Handle 1-Click Import Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_sql') {
    if (!$conn) {
        $import_result = [
            'status' => 'error',
            'message' => 'Cannot import: Database is not connected. ' . $connection_error
        ];
    } elseif (!file_exists($sql_file)) {
        $import_result = [
            'status' => 'error',
            'message' => 'SQL file not found at: ' . htmlspecialchars($sql_file)
        ];
    } else {
        $sql_content = file_get_contents($sql_file);
        
        // Remove comments
        $lines = explode("\n", $sql_content);
        $clean_sql = "";
        foreach ($lines as $line) {
            $trim_line = trim($line);
            if (strpos($trim_line, "--") === 0 || strpos($trim_line, "/*") === 0 || $trim_line === "") {
                continue;
            }
            $clean_sql .= $line . "\n";
        }

        // Split queries by semicolon outside quotes
        $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
        $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

        $queries = preg_split('/;\s*[\r\n]+/', $clean_sql);
        $executed = 0;
        $errors = [];

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if ($conn->query($query)) {
                    $executed++;
                } else {
                    // Ignore benign errors like table already exists if drop table was missing
                    if ($conn->errno !== 1050 && $conn->errno !== 1060) {
                        $errors[] = $conn->error;
                    }
                }
            }
        }

        $conn->query("SET FOREIGN_KEY_CHECKS = 1;");

        // Re-inspect tables after import
        $t_res = $conn->query("SHOW TABLES");
        $tables_found = [];
        if ($t_res) {
            while ($row = $t_res->fetch_array()) {
                $tname = $row[0];
                $c_res = $conn->query("SELECT COUNT(*) FROM `{$tname}`");
                $cnt = $c_res ? $c_res->fetch_array()[0] : 0;
                $tables_found[$tname] = $cnt;
            }
        }

        // Re-check admin
        if (isset($tables_found['users'])) {
            $u_res = $conn->query("SELECT id, username, firstname, lastname, type, status FROM `users` WHERE type = 1 LIMIT 1");
            if ($u_res && $u_res->num_rows > 0) {
                $admin_user = $u_res->fetch_assoc();
            }
        }

        $import_result = [
            'status' => 'success',
            'message' => "Database imported successfully! Executed {$executed} queries.",
            'errors' => $errors
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORRS - Aiven Cloud MySQL 1-Click Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 2rem 1rem;
        }
        .main-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
            max-width: 850px;
            width: 100%;
            overflow: hidden;
        }
        .header-bar {
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            padding: 1.5rem 2rem;
            color: #fff;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        .info-tile {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            height: 100%;
        }
        .table-badge {
            background: #334155;
            color: #e2e8f0;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            display: inline-block;
            margin: 0.25rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="main-card">
    <div class="header-bar d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1"><i class="fa-solid fa-train-subway me-2"></i> ORRS Database Setup</h4>
            <p class="mb-0 small opacity-75">1-Click Aiven Cloud MySQL & Render Auto-Installer</p>
        </div>
        <div>
            <?php if ($connection_status): ?>
                <span class="status-badge bg-success text-white"><i class="fa-solid fa-circle-check me-1"></i> MySQL Connected</span>
            <?php else: ?>
                <span class="status-badge bg-danger text-white"><i class="fa-solid fa-circle-xmark me-1"></i> Connection Failed</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="p-4">
        <!-- Alerts -->
        <?php if ($import_result): ?>
            <?php if ($import_result['status'] === 'success'): ?>
                <div class="alert alert-success border-0 d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-check-double fs-4 me-3"></i>
                    <div>
                        <strong>Awesome!</strong> <?= htmlspecialchars($import_result['message']) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger border-0 d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
                    <div>
                        <strong>Import Error:</strong> <?= htmlspecialchars($import_result['message']) ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$connection_status): ?>
            <div class="alert alert-warning border-0" role="alert">
                <h6 class="alert-heading"><i class="fa-solid fa-plug-circle-xmark me-2"></i> Database Connection Error</h6>
                <p class="mb-1 small font-monospace"><?= htmlspecialchars($connection_error ?? 'Unable to connect') ?></p>
                <hr class="my-2">
                <p class="mb-0 small text-muted">
                    Make sure your Aiven MySQL credentials (DB_SERVER, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_NAME) are set properly in Render Environment Variables.
                </p>
            </div>
        <?php endif; ?>

        <!-- Database Details Grid -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="info-tile">
                    <div class="text-muted small mb-1"><i class="fa-solid fa-server me-1"></i> Host / Server</div>
                    <div class="fw-bold font-monospace text-truncate"><?= htmlspecialchars($db_host) ?>:<?= htmlspecialchars($db_port) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-tile">
                    <div class="text-muted small mb-1"><i class="fa-solid fa-database me-1"></i> Database</div>
                    <div class="fw-bold font-monospace text-truncate"><?= htmlspecialchars($db_name) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-tile">
                    <div class="text-muted small mb-1"><i class="fa-solid fa-shield-halved me-1"></i> SSL Encryption</div>
                    <div>
                        <?php if ($db_ssl): ?>
                            <span class="badge bg-primary">Enabled (MYSQLI_CLIENT_SSL)</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Disabled</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Status & Tables -->
        <div class="card bg-dark border-secondary mb-4">
            <div class="card-header bg-dark border-secondary text-light d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-table me-2"></i> Database Tables (<?= count($tables_found) ?>)</span>
                <span class="badge bg-secondary">Dump: <?= htmlspecialchars(basename($sql_file)) ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($tables_found)): ?>
                    <p class="text-warning mb-0"><i class="fa-solid fa-circle-info me-1"></i> Database is currently empty! Click "1-Click Import SQL" below to install all tables and default data.</p>
                <?php else: ?>
                    <div>
                        <?php foreach ($tables_found as $tname => $tcnt): ?>
                            <span class="table-badge">
                                <i class="fa-solid fa-cube text-primary me-1"></i> <strong><?= htmlspecialchars($tname) ?></strong> (<?= $tcnt ?> rows)
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Credentials Box -->
        <?php if ($admin_user || isset($tables_found['users'])): ?>
            <div class="alert alert-info border-0 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><i class="fa-solid fa-user-shield me-2"></i> Admin Account Ready</h6>
                        <div class="small">
                            <strong>Username:</strong> <code>admin</code> &nbsp;|&nbsp; 
                            <strong>Default Password:</strong> <code>admin123</code>
                        </div>
                    </div>
                    <a href="admin/login.php" class="btn btn-primary btn-sm px-3"><i class="fa-solid fa-right-to-bracket me-1"></i> Login to Admin</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2">
            <div>
                <a href="index.php" class="btn btn-outline-light"><i class="fa-solid fa-house me-1"></i> View Website</a>
                <a href="admin/login.php" class="btn btn-outline-primary"><i class="fa-solid fa-lock me-1"></i> Admin Panel</a>
            </div>
            <div>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="import_sql">
                    <button type="submit" class="btn btn-success px-4" <?= !$connection_status ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-file-import me-1"></i> 
                        <?= empty($tables_found) ? '1-Click Import Database (.SQL)' : 'Re-Import Database (.SQL)' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
