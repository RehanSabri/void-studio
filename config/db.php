<?php
// ─────────────────────────────────────────────────
//  config/db.php — Database connection + helpers
//  Supports both env-var config (Railway/production)
//  and XAMPP localhost defaults for local development.
// ─────────────────────────────────────────────────

// ─── Mail config ─────────────────────────────────
// Change these to your real addresses before deploying.
define('MAIL_TO', getenv('MAIL_TO') ?: 'your@email.com');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@void.studio');

// ─── PDO singleton ───────────────────────────────
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Read from environment variables (Railway, etc.)
    // Falls back to XAMPP localhost defaults for local dev.
    $host = getenv('MYSQLHOST') ?: '127.0.0.1';
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
    $db = getenv('MYSQLDATABASE') ?: 'void_studio';
    $port = (int)(getenv('MYSQLPORT') ?: 3306);

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4;connect_timeout=5";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    return $pdo;
}

// ─── Client IP helper ────────────────────────────
function get_client_ip(): string
{
    $keys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR', // Proxies / load balancers
        'HTTP_X_REAL_IP', // Nginx proxy
        'REMOTE_ADDR', // Direct connection
    ];

    foreach ($keys as $key) {
        $val = $_SERVER[$key] ?? '';
        if ($val === '')
            continue;

        // X-Forwarded-For can be a comma-separated list; take the first (client) IP.
        $ip = trim(explode(',', $val)[0]);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }

    // Fallback — even private-range IPs are better than nothing in local dev.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}