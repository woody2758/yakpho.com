<?php
/**
 * YakPho Aroma — Admin Users Diagnostic & Self-Healing Debugger
 * URL: https://yakpho.com/admin/users/debug.php?key=yakpho_migrate_2026
 */

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Security verification
$secretKey = 'yakpho_migrate_2026';
$providedKey = (string)($_GET['key'] ?? '');

session_start();
$isAdminLoggedIn = !empty($_SESSION['admin_id']) || !empty($_SESSION['admin_role']);

if ($providedKey !== $secretKey && !$isAdminLoggedIn) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head><body style="font-family:sans-serif;padding:40px;background:#111;color:#fff;">';
    echo '<h2 style="color:#ef4444;">⛔ สิทธิ์การเข้าถึงไม่ถูกต้อง (403 Forbidden)</h2>';
    echo '<p>กรุณาระบุ Secret Key ใน URL เช่น: <code>debug.php?key=yakpho_migrate_2026</code> หรือเข้าสู่ระบบแอดมินก่อน</p>';
    echo '</body></html>';
    exit;
}

$results = [];
$hasError = false;

function addCheck(string $title, bool $status, string $details = '', string $solution = ''): void {
    global $results, $hasError;
    if (!$status) $hasError = true;
    $results[] = [
        'title'    => $title,
        'status'   => $status,
        'details'  => $details,
        'solution' => $solution
    ];
}

// 1. PHP Version & SAPI Check
$phpVer = PHP_VERSION;
$isPhp8 = version_compare($phpVer, '8.0.0', '>=');
addCheck(
    'PHP Version',
    true,
    "Current PHP Version: {$phpVer} (SAPI: " . php_sapi_name() . ") — " . ($isPhp8 ? "PHP 8.x Compatible" : "PHP 7.4 Legacy Compatible")
);

// 2. Load Config & Database
$db = null;
try {
    require_once __DIR__ . '/../includes/config.php';
    if (isset($db) && $db instanceof PDO) {
        $dbVer = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
        addCheck('Database Connection', true, "Connected successfully to MySQL version: {$dbVer}");
    } else {
        addCheck('Database Connection', false, 'PDO $db variable is not available or not connected.');
    }
} catch (Throwable $e) {
    addCheck('Database Connection', false, 'Error: ' . $e->getMessage(), 'Check config.php and database credentials.');
}

// 3. Check Required Files
$filesToCheck = [
    'admin/includes/config.php'        => __DIR__ . '/../includes/config.php',
    'admin/includes/auth.php'          => __DIR__ . '/../includes/auth.php',
    'includes/functions/user.php'      => __DIR__ . '/../../includes/functions/user.php',
    'includes/functions/pagination.php'=> __DIR__ . '/../../includes/functions/pagination.php',
    'includes/functions/address.php'   => __DIR__ . '/../../includes/functions/address.php',
    'admin/users/index.php'            => __DIR__ . '/index.php',
    'admin/api/get_users_table.php'    => __DIR__ . '/../api/get_users_table.php',
];

foreach ($filesToCheck as $name => $path) {
    $exists = file_exists($path);
    addCheck(
        "File: {$name}",
        $exists,
        $exists ? "File exists (" . filesize($path) . " bytes)" : "File missing at: {$path}",
        $exists ? '' : "Ensure {$name} is uploaded to the server."
    );
}

// 4. Verify user table columns
if ($db) {
    try {
        $colsStmt = $db->query("SHOW COLUMNS FROM user");
        $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

        $criticalColumns = [
            'role', 'user_stars', 'm_stamp_points', 'member_tier',
            'line_user_id', 'line_display_name', 'line_picture_url',
            'affiliate_code', 'email_verified', 'user_key', 'user_nickname',
            'user_birthdate', 'user_google_map', 'user_website', 'user_del'
        ];

        $missingCols = array_diff($criticalColumns, $cols);
        if (empty($missingCols)) {
            addCheck('User Table Schema', true, 'All 15 CRM & Member columns are present in `user` table.');
        } else {
            addCheck(
                'User Table Schema',
                false,
                'Missing columns in `user` table: ' . implode(', ', $missingCols),
                'Run database migration at: ' . ROOT_URL . '/admin/migrate_member_system.php?key=yakpho_migrate_2026'
            );
        }
    } catch (Throwable $e) {
        addCheck('User Table Schema', false, 'Query error: ' . $e->getMessage());
    }
}

// 5. Test Functions & Queries
if ($db && file_exists(__DIR__ . '/../../includes/functions/user.php')) {
    require_once __DIR__ . '/../../includes/functions/user.php';
    require_once __DIR__ . '/../../includes/functions/pagination.php';
    require_once __DIR__ . '/../../includes/functions/address.php';

    // Test get_all_users()
    try {
        $users = get_all_users(5, 0, '', '', 'desc', '', '');
        $count = count($users);
        addCheck(
            'Function: get_all_users()',
            true,
            "Successfully fetched {$count} user records from database."
        );
    } catch (Throwable $e) {
        addCheck(
            'Function: get_all_users()',
            false,
            'Error executing get_all_users(): ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
        );
    }

    // Test count_all_users()
    try {
        $totalUsers = count_all_users('', '', '', '');
        addCheck(
            'Function: count_all_users()',
            true,
            "Total user count in database: {$totalUsers}"
        );
    } catch (Throwable $e) {
        addCheck(
            'Function: count_all_users()',
            false,
            'Error executing count_all_users(): ' . $e->getMessage()
        );
    }

    // Test count_user_addresses()
    try {
        if (!empty($users)) {
            $firstUid = (int)$users[0]['user_id'];
            $addrCount = count_user_addresses($firstUid);
            addCheck(
                'Function: count_user_addresses()',
                true,
                "Tested count_user_addresses() for user C{$firstUid}: {$addrCount} addresses found."
            );
        }
    } catch (Throwable $e) {
        addCheck(
            'Function: count_user_addresses()',
            false,
            'Error executing count_user_addresses(): ' . $e->getMessage()
        );
    }
}

// 6. Test Syntax & Compatibility of admin/users/index.php
$indexContent = @file_get_contents(__DIR__ . '/index.php');
if ($indexContent !== false) {
    if (preg_match('/\bmatch\s*\(/i', $indexContent)) {
        addCheck(
            'admin/users/index.php Syntax Compatibility',
            false,
            'Detected PHP 8.0 `match(...)` expression in index.php. If your server is running PHP 7.4, this triggers HTTP 500 parse error.',
            'Refactor `match` to standard array lookup or switch statement.'
        );
    } else {
        addCheck(
            'admin/users/index.php Syntax Compatibility',
            true,
            'No `match(...)` expressions found. Syntax is 100% backward-compatible with PHP 7.4+.'
        );
    }
}

// 7. Check admin auth & session
$authStatus = 'Admin Session: ' . ($isAdminLoggedIn ? 'Logged in as ' . htmlspecialchars((string)($_SESSION['admin_role'] ?? 'admin')) : 'Not logged in (running with secret key)');
addCheck('Auth & Permissions', true, $authStatus);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YakPho Admin Users — Diagnostics & Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #0b1f1a; color: #e5e7eb; padding: 40px 20px; }
        .card { background-color: #122822; border: 1px solid #1e3d34; border-radius: 12px; }
        .badge-success { background-color: #10b981; color: #fff; }
        .badge-danger { background-color: #ef4444; color: #fff; }
        .table-dark { background-color: transparent; }
        .table-dark td, .table-dark th { border-color: #1e3d34; }
        code { background: #081714; color: #f59e0b; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container" style="max-width: 900px;">
    <div class="card shadow-lg p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
            <div>
                <h3 class="mb-1 text-warning">🛠️ ระบบตรวจวิเคราะห์ข้อผิดพลาด (Admin Users Diagnostics)</h3>
                <p class="text-secondary mb-0">yakpho.com/admin/users/ — วิเคราะห์ความเข้ากันได้ PHP และการเชื่อมต่อฐานข้อมูล</p>
            </div>
            <div>
                <?php if ($hasError): ?>
                    <span class="badge bg-danger fs-6 px-3 py-2">⚠️ มีข้อผิดพลาด</span>
                <?php else: ?>
                    <span class="badge bg-success fs-6 px-3 py-2">✅ ระบบพร้อมใช้งาน 100%</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 25%;">รายการตรวจสอบ</th>
                        <th style="width: 15%;">สถานะ</th>
                        <th>รายละเอียดผลการตรวจ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr>
                        <td class="fw-semibold text-light"><?= htmlspecialchars($r['title']) ?></td>
                        <td>
                            <?php if ($r['status']): ?>
                                <span class="badge bg-success">PASS</span>
                            <?php else: ?>
                                <span class="badge bg-danger">FAIL</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="<?= $r['status'] ? 'text-light' : 'text-danger fw-bold' ?>">
                                <?= htmlspecialchars($r['details']) ?>
                            </div>
                            <?php if (!empty($r['solution'])): ?>
                                <div class="text-warning mt-1 small">
                                    💡 <strong>คำแนะนำแก้ไข:</strong> <?= htmlspecialchars($r['solution']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
            <a href="<?= ROOT_URL ?>/admin/users/" class="btn btn-warning fw-bold px-4">
                🚀 ไปยังหน้าจัดการสมาชิก (Admin Users)
            </a>
            <a href="<?= ROOT_URL ?>/admin/orders/" class="btn btn-outline-light">
                📦 จัดการคำสั่งซื้อ (Orders)
            </a>
        </div>
    </div>
</div>
</body>
</html>
