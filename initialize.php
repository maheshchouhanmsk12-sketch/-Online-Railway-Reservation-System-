<?php
$dev_data = array('id'=>'-1','firstname'=>'Developer','lastname'=>'','username'=>'dev_oretnom','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');

// Support Environment Variables for Base URL (Render & Reverse Proxies)
$env_base_url = getenv('BASE_URL') ?: getenv('RENDER_EXTERNAL_URL');
if (!empty($env_base_url)) {
    $auto_base_url = rtrim($env_base_url, '/') . '/';
} else {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = $is_https ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $script_dir = preg_replace('#/(admin|classes|database|inc|plugins|uploads).*$#', '', $script_dir);
    $script_dir = trim($script_dir, '/.');
    $auto_base_url = $protocol . $host . ($script_dir !== '' ? '/' . $script_dir : '') . '/';
}

if(!defined('base_url')) define('base_url', $auto_base_url);
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('dev_data')) define('dev_data',$dev_data);

// 1. Check for Full Service URI / Connection String (e.g. from Aiven Service URI)
$service_uri = getenv('DATABASE_URL') ?: getenv('SERVICE_URI') ?: getenv('MYSQL_URL') ?: getenv('AIVEN_URL') ?: getenv('Service URI');
$uri_parts = [];
if (!empty($service_uri)) {
    $parsed = parse_url($service_uri);
    if ($parsed && isset($parsed['host'])) {
        $uri_parts['host'] = $parsed['host'];
        if (isset($parsed['port'])) $uri_parts['port'] = (int)$parsed['port'];
        if (isset($parsed['user'])) $uri_parts['user'] = urldecode($parsed['user']);
        if (isset($parsed['pass'])) $uri_parts['pass'] = urldecode($parsed['pass']);
        if (isset($parsed['path'])) $uri_parts['name'] = trim($parsed['path'], '/');
    }
}

// 2. Resolve Host / Server (Accepts DB_SERVER, DB_HOST, MYSQL_HOST, Host, HOST)
$resolved_host = $uri_parts['host'] ?? (
    getenv('DB_SERVER') ?:
    getenv('DB_HOST') ?:
    getenv('MYSQL_HOST') ?:
    getenv('MYSQLHOST') ?:
    getenv('Host') ?:
    getenv('HOST') ?:
    'localhost'
);

// 3. Resolve Port (Accepts DB_PORT, MYSQL_PORT, Port)
$resolved_port = $uri_parts['port'] ?? (int)(
    getenv('DB_PORT') ?:
    getenv('MYSQL_PORT') ?:
    getenv('MYSQLPORT') ?:
    getenv('Port') ?:
    3306
);

// 4. Resolve Username (Accepts DB_USERNAME, DB_USER, MYSQL_USER, User, USER)
$resolved_user = $uri_parts['user'] ?? (
    getenv('DB_USERNAME') ?:
    getenv('DB_USER') ?:
    getenv('MYSQL_USER') ?:
    getenv('MYSQLUSER') ?:
    getenv('User') ?:
    getenv('USER') ?:
    'root'
);

// 5. Resolve Password (Accepts DB_PASSWORD, DB_PASS, MYSQL_PASSWORD, Password, PASSWORD)
$resolved_pass = $uri_parts['pass'] ?? (
    getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (
        getenv('DB_PASS') !== false ? getenv('DB_PASS') : (
            getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : (
                getenv('Password') !== false ? getenv('Password') : (
                    getenv('PASSWORD') !== false ? getenv('PASSWORD') : ''
                )
            )
        )
    )
);

// 6. Resolve Database Name (Accepts DB_NAME, DB_DATABASE, MYSQL_DATABASE, Database name)
$resolved_name = $uri_parts['name'] ?? (
    getenv('DB_NAME') ?:
    getenv('DB_DATABASE') ?:
    getenv('MYSQL_DATABASE') ?:
    getenv('Database name') ?:
    getenv('DATABASE_NAME') ?:
    'orrs_db'
);

// 7. Resolve SSL (Auto-enables for all cloud databases e.g. aivencloud.com)
$resolved_ssl = (getenv('DB_SSL') !== false)
    ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN)
    : ($resolved_host !== 'localhost' && $resolved_host !== '127.0.0.1');

if(!defined('DB_SERVER')) define('DB_SERVER', $resolved_host);
if(!defined('DB_PORT')) define('DB_PORT', $resolved_port);
if(!defined('DB_USERNAME')) define('DB_USERNAME', $resolved_user);
if(!defined('DB_PASSWORD')) define('DB_PASSWORD', $resolved_pass);
if(!defined('DB_NAME')) define('DB_NAME', $resolved_name);
if(!defined('DB_SSL')) define('DB_SSL', $resolved_ssl);
?>
