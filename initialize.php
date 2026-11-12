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

// Database Configurations with Environment Variables Support (Aiven Cloud & Docker)
if(!defined('DB_SERVER')) define('DB_SERVER', getenv('DB_SERVER') ?: "localhost");
if(!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if(!defined('DB_USERNAME')) define('DB_USERNAME', getenv('DB_USERNAME') ?: "root");
if(!defined('DB_PASSWORD')) define('DB_PASSWORD', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "");
if(!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: "orrs_db");
if(!defined('DB_SSL')) define('DB_SSL', getenv('DB_SSL') !== false ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : (DB_SERVER !== 'localhost' && DB_SERVER !== '127.0.0.1'));
?>