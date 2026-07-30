<?php
/**
 * Simple Environment Loader for Love Cakes POS
 */
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $env_vars = null;
        if ($env_vars === null) {
            $env_vars = [];
            $env_path = __DIR__ . '/../.env';
            if (file_exists($env_path)) {
                $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || str_starts_with($line, '#')) continue;
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                            $value = substr($value, 1, -1);
                        }
                        $env_vars[$name] = $value;
                    }
                }
            }
        }
        return array_key_exists($key, $env_vars) ? $env_vars[$key] : $default;
    }
}

// Define BASE_URL from env or dynamic detection
if (!defined('BASE_URL')) {
    $env_url = env('BASE_URL');
    if (!empty($env_url)) {
        define('BASE_URL', rtrim($env_url, '/') . '/');
    } else {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $is_localhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
        $folder = $is_localhost ? '/pos-lovecakes/' : '/';
        define('BASE_URL', $protocol . $host . $folder);
    }
}
