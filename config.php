<?php
// ============================================================
//  config.php  —  Database connection & global helpers
//  Student Management System | SE322
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Database credentials (XAMPP defaults) -----------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sms_db');

// ---- Connect -------------------------------------------------
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo '<!DOCTYPE html><html><head>
    <meta charset="UTF-8">
    <style>
        body{font-family:sans-serif;background:#fef2f2;display:flex;align-items:center;
             justify-content:center;min-height:100vh;margin:0;}
        .box{background:#fff;border:1px solid #fca5a5;border-radius:12px;padding:36px 44px;
             max-width:480px;text-align:center;}
        h2{color:#dc2626;margin-bottom:12px;}
        p{color:#374151;line-height:1.6;}
        code{background:#f3f4f6;padding:2px 8px;border-radius:4px;font-size:13px;}
    </style></head><body>
    <div class="box">
        <h2>⚠ Database Connection Failed</h2>
        <p><strong>Error:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Please make sure:<br>
        1. XAMPP <strong>Apache</strong> &amp; <strong>MySQL</strong> are running<br>
        2. You imported <code>database.sql</code> in phpMyAdmin<br>
        3. DB name is <code>sms_db</code></p>
    </div></body></html>';
    exit();
}

$conn->set_charset('utf8mb4');

// ---- Helpers -------------------------------------------------

/**
 * Sanitize user input (trim + strip tags + escape for SQL)
 */
function clean($conn, $val) {
    return $conn->real_escape_string(strip_tags(trim((string)$val)));
}

/**
 * Redirect to a URL and stop execution
 */
function go($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Set a session flash message
 */
function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Return grade letter and CSS class from percentage
 */
function gradeInfo($pct) {
    if ($pct >= 80) return ['A+', 'success'];
    if ($pct >= 70) return ['A',  'success'];
    if ($pct >= 60) return ['B+', 'info'];
    if ($pct >= 50) return ['B',  'info'];
    if ($pct >= 40) return ['C',  'warning'];
    if ($pct >= 33) return ['D',  'warning'];
    return ['F', 'danger'];
}

/**
 * Two-letter avatar from a name
 */
function initials($name) {
    $parts = explode(' ', trim($name));
    $a = strtoupper(substr($parts[0], 0, 1));
    $b = isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '';
    return $a . $b;
}
?>
