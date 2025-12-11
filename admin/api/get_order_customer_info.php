<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['orders_id'])) {
        throw new Exception('Order ID is required');
    }

    $orders_id = (int)$_GET['orders_id'];

    // Get Order Info + User + Address + Shipping + Items
    $sql = "SELECT o.*, 
            u.user_name, u.user_lastname, u.user_mobile,
            a.addr_name, a.addr_detail, a.addr_detail2, a.addr_postcode, a.addr_mobile,
            p.name_th as addr_province,
            t.transcat_name, t.transcat_link,
            (SELECT COUNT(*) FROM ordetail WHERE orders_id = o.orders_id) as item_count
            FROM orders o
            LEFT JOIN user u ON o.user_id = u.user_id
            LEFT JOIN addr a ON o.addr_id = a.addr_id
            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
            LEFT JOIN transcat t ON o.transcat_id = t.transcat_id
            WHERE o.orders_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$orders_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    if (empty($order['orders_tracking'])) {
        throw new Exception('กรุณาระบุเลขพัสดุก่อนคัดลอกข้อมูลครับ');
    }

    // Format text for clipboard
    // Format:
    // ชื่อลูกค้า: Name (ID)
    // ชื่อผู้ส่ง ไทยเฮิร์บออนไลน์ (0839971606)
    // ชื่อผู้รับ
    // Name (ID)
    // Address
    // Tel
    //
    // คำสั่งซื้อ
    // Link
    //
    // จัดส่งโดย: Shipping
    // เลขพัสดุทั้งหมด X
    // 1/X - Link

    $customerName = $order['user_name'] . ' ' . $order['user_lastname'];
    $customerId = 'C' . $order['user_id']; // Assuming ID format
    
    $receiverName = $order['addr_name'] ? $order['addr_name'] : $customerName;
    $receiverTel = $order['addr_mobile'] ? $order['addr_mobile'] : $order['user_mobile'];
    
    $address = $order['addr_detail'];
    if ($order['addr_detail2']) $address .= "\n" . $order['addr_detail2'];
    $address .= "\n" . $order['addr_province'] . ' ' . $order['addr_postcode'];

    $orderLink = "https://yakpho.com/o/" . $order['orders_no']; 
    
    $shippingName = $order['transcat_name'] ? $order['transcat_name'] : '-';
    $trackingText = "";
    
    if ($order['orders_tracking']) {
        $trackings = explode(',', $order['orders_tracking']);
        $count = count($trackings);
        $trackingText .= "เลขพัสดุทั้งหมด $count\n";
        foreach ($trackings as $i => $track) {
            $track = trim($track);
            $link = $order['transcat_link'] ? str_replace('xxx', $track, $order['transcat_link']) : $track;
             // If link doesn't have xxx, just use link.
            if (strpos($order['transcat_link'], 'xxx') === false && $order['transcat_link']) {
                    $link = $order['transcat_link'] . $track;
            }
            $num = $i + 1;
            $trackingText .= "$num/$count - $link\n";
        }
    } else {
        $trackingText = "ยังไม่ระบุเลขพัสดุ";
    }

    $text = "ชื่อลูกค้า: $customerName($customerId)\n";
    $text .= "ชื่อผู้ส่ง ไทยเฮิร์บออนไลน์ (0839971606)\n";
    $text .= "ชื่อผู้รับ\n";
    $text .= "$receiverName($customerId)\n";
    $text .= "$address\n";
    $text .= "Tel.$receiverTel\n\n";
    $text .= "คำสั่งซื้อ\n";
    $text .= "$orderLink\n\n";
    $text .= "จัดส่งโดย: $shippingName\n";
    $text .= $trackingText;

    echo json_encode(['success' => true, 'text' => $text]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
