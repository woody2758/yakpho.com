<?php
// Session Timeout (1 Year)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 31536000);
    ini_set('session.gc_maxlifetime', 31536000);
    session_start();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=woodysay_esther;charset=utf8mb4", "woodysay_esther", "cYuekzwRgSgNNAMqJhwA");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Error (Host Config): " . $e->getMessage());
}

// ----------------------------------------------------------------------
// REMEMBER ME: Auto-login Check
// ----------------------------------------------------------------------
if ((!isset($_SESSION['admin_logged']) && !isset($_SESSION['scanner_logged'])) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $db->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tokenData) {
        $stmtUser = $db->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
        $stmtUser->execute([$tokenData['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $role = strtolower($user['role']);
            $logged_in_any = false;
            
            // Check Admin roles
            $admin_roles = ['owner', 'admin', 'manager', 'editor', 'staff'];
            if (in_array($role, $admin_roles)) {
                $_SESSION['admin_logged']   = true;
                $_SESSION['admin_id']       = $user['user_id'];
                $_SESSION['admin_username'] = $user['user_username'];
                $_SESSION['admin_role']     = $user['role'];
                $_SESSION['admin_name']     = $user['user_name'];
                $_SESSION['admin_picture']  = $user['user_picture'] ?? 'default.png';
                $logged_in_any = true;
            }
            
            // Check Scanner roles
            $scanner_roles = ['staff', 'manager', 'admin', 'owner', 'packer'];
            if (in_array($role, $scanner_roles)) {
                $_SESSION['scanner_logged'] = true;
                $_SESSION['scanner_user_id'] = $user['user_id'];
                $_SESSION['scanner_name'] = $user['user_name'] . ' ' . $user['user_lastname'];
                $_SESSION['scanner_role']     = $user['role'];
                $logged_in_any = true;
            }
            
            if (!$logged_in_any) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } else {
             setcookie('remember_token', '', time() - 3600, '/');
        }
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
// ----------------------------------------------------------------------

$ver = "?v=1.0.0"; // เปลี่ยนเป็นเลขเวอร์ชั่นคงที่เพื่อให้ Browser ทำการ Cache ไฟล์ CSS/JS ได้ (เพิ่มความเร็วในการโหลด)
// Backend-only config
define("ROOT_URL", "https://esther.rest");
define("BASE_URL", ROOT_URL);
define("URL_PATH", "https://esther.rest");
define("ADMIN_URL", ROOT_URL . "/admin");
define("ADMIN_ASSETS", ADMIN_URL . "/assets");

define("UPLOAD_DIR", __DIR__ . "/../../uploads");
define("SYSTEM_LOG", __DIR__ . "/../../logs");
require_once __DIR__ . "/functions.php";


define("ADMIN_NAME", "Esther.Rest");
