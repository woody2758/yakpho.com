<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_details') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid User ID']);
            exit;
        }
        
        // Fetch User Info
        $stmt_user = $db->prepare("SELECT user_id, user_name, user_lastname, user_mobile, user_tel, user_note, user_google_map FROM user WHERE user_id = ? LIMIT 1");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Fetch Orders
        $stmt_orders = $db->prepare("
            SELECT o.orders_id, o.orders_no, o.orders_date, o.orders_grandtotal, o.orders_status,
                   COALESCE(SUM(od.ordetail_qty), 0) as total_qty
            FROM orders o
            LEFT JOIN ordetail od ON o.orders_id = od.orders_id
            WHERE o.user_id = ? AND o.orders_del = 0 AND o.orders_status = 6
            GROUP BY o.orders_id
            ORDER BY o.orders_date DESC
        ");
        $stmt_orders->execute([$user_id]);
        $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

        // Calculate days since previous order
        $order_details = [];
        for ($i = 0; $i < count($orders); $i++) {
            $o = $orders[$i];
            
            // Calculate days since previous order (the one before this chronologically)
            // Since they are DESC, the next one in array is the previous one chronologically.
            $days_since_prev = "-";
            if (isset($orders[$i+1])) {
                $date_current = new DateTime($o['orders_date']);
                $date_prev = new DateTime($orders[$i+1]['orders_date']);
                $diff = $date_prev->diff($date_current);
                $days_since_prev = $diff->days;
            }
            
            $o['days_since_prev'] = $days_since_prev;
            
            $order_details[] = $o;
        }

        // Calculate Average Days
        $avg_days = 0;
        if (count($orders) >= 2) {
            $first_order = strtotime(end($orders)['orders_date']);
            $last_order = strtotime($orders[0]['orders_date']);
            if ($last_order > $first_order) {
                $days_diff = round(($last_order - $first_order) / (60 * 60 * 24));
                $avg_days = round($days_diff / (count($orders) - 1));
            }
        }

        // Fetch Follow-ups
        $stmt_fu = $db->prepare("
            SELECT f.*, u.user_name as admin_name 
            FROM customer_followups f
            LEFT JOIN user u ON f.admin_id = u.user_id
            WHERE f.user_id = ?
            ORDER BY f.followup_date DESC, f.created_at DESC
        ");
        $stmt_fu->execute([$user_id]);
        $followups = $stmt_fu->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'user' => $user,
            'orders' => $order_details,
            'followups' => $followups,
            'stats' => [
                'total_orders' => count($orders),
                'avg_days' => $avg_days
            ]
        ]);
        exit;
        
    } elseif ($action === 'save_followup') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $detail = trim($_POST['detail'] ?? '');
        $admin_id = $_SESSION['admin_id'] ?? ($_SESSION['scanner_user_id'] ?? 0);
        
        if ($user_id > 0 && !empty($date) && !empty($detail)) {
            $stmt = $db->prepare("INSERT INTO customer_followups (user_id, admin_id, followup_date, followup_detail) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $admin_id, $date, $detail]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
        }
    } elseif ($action === 'save_user_note') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        
        if ($user_id > 0) {
            $stmt = $db->prepare("UPDATE user SET user_note = ? WHERE user_id = ?");
            $stmt->execute([$note, $user_id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid User ID']);
        }
        exit;
    } elseif ($action === 'save_user_map') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $google_map = trim($_POST['google_map'] ?? '');
        
        if ($user_id > 0) {
            $stmt = $db->prepare("UPDATE user SET user_google_map = ? WHERE user_id = ?");
            $stmt->execute([$google_map, $user_id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid User ID']);
        }
        exit;
    } elseif ($action === 'get_order_summary') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid User ID']);
            exit;
        }

        // Fetch Orders
        $stmt_orders = $db->prepare("
            SELECT o.orders_id, o.orders_no, o.orders_date, o.orders_grandtotal
            FROM orders o
            WHERE o.user_id = ? AND o.orders_del = 0 AND o.orders_status = 6
            ORDER BY o.orders_date DESC
        ");
        $stmt_orders->execute([$user_id]);
        $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

        $order_details = [];
        foreach ($orders as $o) {
            $stmt_products = $db->prepare("
                SELECT pt.product_name, p.product_picture, od.ordetail_qty
                FROM ordetail od
                JOIN product p ON od.product_id = p.product_id
                LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
                WHERE od.orders_id = ?
            ");
            $stmt_products->execute([$o['orders_id']]);
            $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$p) {
                $filename = $p['product_picture'];
                if (!empty($filename)) {
                    if (strpos($filename, 'uploads/products/') === false) {
                        $p['product_image_url'] = ROOT_URL . '/uploads/products/small-' . $filename;
                    } else {
                        $p['product_image_url'] = ROOT_URL . '/' . $filename;
                    }
                } else {
                    $p['product_image_url'] = ROOT_URL . '/admin/assets/images/placeholder.png';
                }
            }
            $o['products'] = $products;
            $order_details[] = $o;
        }

        echo json_encode([
            'status' => 'success',
            'orders' => $order_details
        ]);
        exit;
    }
}
