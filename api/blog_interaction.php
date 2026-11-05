<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Blog Likes & Comments API (Facebook-style)
 * ============================================================
 * Actions:
 *  - toggle_blog_like (POST: blog_id)
 *  - add_comment      (POST: blog_id, parent_id, comment_text) [Requires Login]
 *  - toggle_comment_like (POST: comment_id)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';

/**
 * Ensure blog_comments and blog_likes tables exist (Self-healing)
 */
function ensure_blog_interaction_tables(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `blog_likes` (
            `like_id` INT(11) NOT NULL AUTO_INCREMENT,
            `blog_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL DEFAULT 0,
            `visitor_token` VARCHAR(64) NOT NULL DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`like_id`),
            UNIQUE KEY `uniq_blog_visitor` (`blog_id`, `visitor_token`),
            KEY `idx_blog_id` (`blog_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `blog_comments` (
            `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
            `blog_id` INT(11) NOT NULL,
            `parent_id` INT(11) NOT NULL DEFAULT 0,
            `user_id` INT(11) NOT NULL,
            `comment_text` TEXT NOT NULL,
            `likes_count` INT(11) NOT NULL DEFAULT 0,
            `comment_status` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`comment_id`),
            KEY `idx_blog_parent` (`blog_id`, `parent_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {}
}

/**
 * Format commenter name: nickname first; if empty, first name only
 */
function get_commenter_short_name(array $userRow): string {
    $nick = trim((string)($userRow['user_nickname'] ?? ''));
    if ($nick !== '') {
        return $nick;
    }
    $fullName = trim((string)($userRow['user_name'] ?? ''));
    if ($fullName !== '') {
        $parts = preg_split('/\s+/u', $fullName);
        if (!empty($parts[0])) {
            return $parts[0];
        }
        return $fullName;
    }
    $lineName = trim((string)($userRow['line_display_name'] ?? ''));
    if ($lineName !== '') {
        return $lineName;
    }
    return 'สมาชิก';
}

try {
    ensure_blog_interaction_tables($db);

    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode((string)$rawInput, true);
    $payload = is_array($jsonInput) ? $jsonInput : $_POST;

    $action = trim((string)($_GET['action'] ?? $payload['action'] ?? ''));
    $userId = (int)($_SESSION['user_id'] ?? 0);

    // Visitor identifier for blog post likes (works for logged-in or session)
    if (empty($_SESSION['blog_visitor_token'])) {
        $_SESSION['blog_visitor_token'] = bin2hex(random_bytes(16));
    }
    $visitorToken = $userId > 0 ? ('u_' . $userId) : ('s_' . $_SESSION['blog_visitor_token']);

    switch ($action) {
        case 'toggle_blog_like':
            $blogId = (int)($payload['blog_id'] ?? 0);
            if ($blogId <= 0) {
                http_response_code(422);
                echo json_encode(['status' => false, 'message' => 'ไม่พบรหัสบทความ']);
                exit;
            }

            $chk = $db->prepare("SELECT like_id FROM blog_likes WHERE blog_id = ? AND visitor_token = ? LIMIT 1");
            $chk->execute([$blogId, $visitorToken]);
            $existingId = $chk->fetchColumn();

            $liked = false;
            if ($existingId) {
                $del = $db->prepare("DELETE FROM blog_likes WHERE like_id = ?");
                $del->execute([$existingId]);
                $liked = false;
            } else {
                $ins = $db->prepare("INSERT IGNORE INTO blog_likes (blog_id, user_id, visitor_token, created_at) VALUES (?, ?, ?, NOW())");
                $ins->execute([$blogId, $userId, $visitorToken]);
                $liked = true;
            }

            $cntStmt = $db->prepare("SELECT COUNT(*) FROM blog_likes WHERE blog_id = ?");
            $cntStmt->execute([$blogId]);
            $totalLikes = (int)$cntStmt->fetchColumn();

            echo json_encode([
                'status'      => true,
                'liked'       => $liked,
                'likes_count' => $totalLikes,
            ]);
            exit;

        case 'add_comment':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode([
                    'status'  => false,
                    'require_login' => true,
                    'message' => 'กรุณาเข้าสู่ระบบก่อนแสดงความคิดเห็น',
                ]);
                exit;
            }

            $blogId      = (int)($payload['blog_id'] ?? 0);
            $parentId    = max(0, (int)($payload['parent_id'] ?? 0));
            $commentText = trim((string)($payload['comment_text'] ?? ''));

            if ($blogId <= 0 || $commentText === '') {
                http_response_code(422);
                echo json_encode(['status' => false, 'message' => 'กรุณาพิมพ์ข้อความความคิดเห็น']);
                exit;
            }

            if (mb_strlen($commentText, 'UTF-8') > 1500) {
                $commentText = mb_substr($commentText, 0, 1500, 'UTF-8');
            }

            $ins = $db->prepare("
                INSERT INTO blog_comments (blog_id, parent_id, user_id, comment_text, likes_count, comment_status, created_at)
                VALUES (?, ?, ?, ?, 0, 1, NOW())
            ");
            $ins->execute([$blogId, $parentId, $userId, $commentText]);
            $commentId = (int)$db->lastInsertId();

            // Fetch commenter display details
            $uStmt = $db->prepare("SELECT user_id, user_name, user_nickname, line_display_name, line_picture_url, user_picture FROM user WHERE user_id = ? LIMIT 1");
            $uStmt->execute([$userId]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $displayName = get_commenter_short_name($uRow);
            $avatarUrl   = !empty($uRow['line_picture_url']) ? (string)$uRow['line_picture_url'] : '';
            $initial     = mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8');

            echo json_encode([
                'status'  => true,
                'message' => 'บันทึกความคิดเห็นเรียบร้อยแล้ว',
                'comment' => [
                    'comment_id'   => $commentId,
                    'blog_id'      => $blogId,
                    'parent_id'    => $parentId,
                    'display_name' => $displayName,
                    'avatar_url'   => $avatarUrl,
                    'initial'      => $initial ?: 'U',
                    'comment_text' => $commentText,
                    'created_text' => 'เมื่อสักครู่',
                    'likes_count'  => 0,
                ],
            ]);
            exit;

        case 'like_comment':
            $commentId = (int)($payload['comment_id'] ?? 0);
            if ($commentId <= 0) {
                http_response_code(422);
                echo json_encode(['status' => false, 'message' => 'ไม่พบรหัสความคิดเห็น']);
                exit;
            }
            $sessionKey = 'liked_comment_' . $commentId;
            if (!empty($_SESSION[$sessionKey])) {
                $db->prepare("UPDATE blog_comments SET likes_count = GREATEST(0, likes_count - 1) WHERE comment_id = ?")->execute([$commentId]);
                unset($_SESSION[$sessionKey]);
                $liked = false;
            } else {
                $db->prepare("UPDATE blog_comments SET likes_count = likes_count + 1 WHERE comment_id = ?")->execute([$commentId]);
                $_SESSION[$sessionKey] = true;
                $liked = true;
            }
            $cStmt = $db->prepare("SELECT likes_count FROM blog_comments WHERE comment_id = ? LIMIT 1");
            $cStmt->execute([$commentId]);
            $cnt = (int)$cStmt->fetchColumn();

            echo json_encode([
                'status'      => true,
                'liked'       => $liked,
                'likes_count' => $cnt,
            ]);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Invalid action']);
            exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

