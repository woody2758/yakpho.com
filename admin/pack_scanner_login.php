<?php
// Set session to last for 1 year (31536000 seconds)
$lifetime = 31536000;
session_set_cookie_params($lifetime);
ini_set('session.gc_maxlifetime', $lifetime);
session_start();
require_once __DIR__ . '/includes/config.php';

// If already logged in, redirect to scanner
if (isset($_SESSION['scanner_logged']) && $_SESSION['scanner_logged'] === true) {
    header("Location: " . ADMIN_URL . "/scanner");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($mobile) || empty($password)) {
        $error = "กรุณากรอกเบอร์โทรศัพท์และรหัสผ่าน";
    } else {
        $stmt = $db->prepare("SELECT * FROM user WHERE user_mobile = ? AND user_stop = 1 AND user_del = 0 LIMIT 1");
        $stmt->execute([$mobile]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['user_password'])) {
            // Check Role - Allow staff, manager, admin, owner, packer
            $allowed_roles = ['staff', 'manager', 'admin', 'owner', 'packer'];
            if (in_array(strtolower($user['role']), $allowed_roles)) {
                $_SESSION['scanner_logged'] = true;
                $_SESSION['scanner_user_id'] = $user['user_id'];
                $_SESSION['scanner_name'] = $user['user_name'] . ' ' . $user['user_lastname'];
                $_SESSION['scanner_role'] = $user['role'];
                
                // ----------------------------------------------------------------------
                // REMEMBER ME: สร้าง Token สำหรับล็อกอินค้างไว้ 1 ปี
                // ----------------------------------------------------------------------
                $expiry_time = time() + (86400 * 365);
                $expiry_datetime = date('Y-m-d H:i:s', $expiry_time);
                $token = bin2hex(random_bytes(32));

                $stmtToken = $db->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmtToken->execute([$user['user_id'], $token, $expiry_datetime]);

                setcookie('remember_token', $token, [
                    'expires' => $expiry_time,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                // ----------------------------------------------------------------------
                
                header("Location: " . ADMIN_URL . "/scanner");
                exit;
            } else {
                $error = "บัญชีของคุณไม่มีสิทธิ์เข้าใช้งานระบบแพ็คกิ้ง";
            }
        } else {
            $error = "เบอร์โทรศัพท์ หรือ รหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>เข้าสู่ระบบ - Packing Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .login-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 40px 30px; width: 100%; max-width: 400px; text-align: center; }
        .logo-icon { background: #4f46e5; color: white; width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 16px rgba(79,70,229,0.2); }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(79,70,229,0.1); border-color: #4f46e5; background: #fff; }
        .btn-login { background: #4f46e5; color: white; border-radius: 12px; padding: 12px; font-weight: 600; font-size: 1.05rem; width: 100%; border: none; margin-top: 15px; transition: all 0.2s; }
        .btn-login:hover, .btn-login:active { background: #4338ca; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-icon">
            <i data-lucide="scan" style="width: 32px; height: 32px;"></i>
        </div>
        <h4 class="fw-bold mb-1">Packing Scanner</h4>
        <p class="text-muted small mb-4">เข้าสู่ระบบสำหรับพนักงานเตรียมจัดส่ง</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small py-2 rounded-3 text-start mb-3">
                <i data-lucide="alert-circle" style="width:16px; height:16px; margin-right:5px; margin-bottom:2px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3 text-start">
                <label class="form-label small fw-bold text-muted">เบอร์โทรศัพท์</label>
                <input type="tel" name="mobile" class="form-control" placeholder="08xxxxxxxx" required autofocus>
            </div>
            <div class="mb-4 text-start">
                <label class="form-label small fw-bold text-muted">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
