<?php
require_once __DIR__ . '/../includes/config.php';
$is_api_call = true;
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$orders_id = isset($_POST['orders_id']) ? (int)$_POST['orders_id'] : 0;
if (empty($orders_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order ID']);
    exit;
}

$upload_dir = __DIR__ . '/../../uploads/packing/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

try {
    $stmt = $db->prepare("SELECT orders_id, orders_no, packing_image FROM orders WHERE orders_id = ?");
    $stmt->execute([$orders_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    $existing_images = [];
    if (!empty($order['packing_image'])) {
        $existing_images = array_values(array_filter(array_map('trim', explode(',', $order['packing_image']))));
    }

    $action = $_POST['action'] ?? 'upload';

    // Handle delete action
    if ($action === 'delete') {
        $filename_to_delete = trim($_POST['filename'] ?? '');
        if (empty($filename_to_delete)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing filename to delete']);
            exit;
        }

        $new_images = array_values(array_filter($existing_images, function($img) use ($filename_to_delete) {
            return $img !== $filename_to_delete;
        }));

        // Safely unlink if file exists and filename doesn't contain path traversal
        if (basename($filename_to_delete) === $filename_to_delete && file_exists($upload_dir . $filename_to_delete)) {
            @unlink($upload_dir . $filename_to_delete);
        }

        $packed_val = !empty($new_images) ? implode(',', $new_images) : null;
        $up = $db->prepare("UPDATE orders SET packing_image = ? WHERE orders_id = ?");
        $up->execute([$packed_val, $orders_id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'ลบรูปภาพเรียบร้อยแล้ว',
            'images' => $new_images,
            'count' => count($new_images)
        ]);
        exit;
    }

    // Handle get action
    if ($action === 'get') {
        echo json_encode([
            'status' => 'success',
            'images' => $existing_images,
            'count' => count($existing_images)
        ]);
        exit;
    }

    // Handle upload
    $uploaded_new = [];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'];

    // Collect all uploaded files into a standardized array
    $files_to_process = [];
    if (isset($_FILES['packing_images'])) {
        $files = $_FILES['packing_images'];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $files_to_process[] = [
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i]
                    ];
                }
            }
        } elseif ($files['error'] === UPLOAD_ERR_OK) {
            $files_to_process[] = [
                'name' => $files['name'],
                'tmp_name' => $files['tmp_name']
            ];
        }
    }

    if (isset($_FILES['packing_image'])) {
        $files = $_FILES['packing_image'];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $files_to_process[] = [
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i]
                    ];
                }
            }
        } elseif ($files['error'] === UPLOAD_ERR_OK) {
            $files_to_process[] = [
                'name' => $files['name'],
                'tmp_name' => $files['tmp_name']
            ];
        }
    }

    if (empty($files_to_process)) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีไฟล์รูปภาพที่อัปโหลด หรือเกิดข้อผิดพลาดในการส่งไฟล์']);
        exit;
    }

    $idx = 0;
    foreach ($files_to_process as $f) {
        $file_info = pathinfo($f['name']);
        $ext = strtolower($file_info['extension'] ?? 'jpg');
        if (!in_array($ext, $allowed_exts)) {
            continue;
        }

        $filename = 'pack_' . $order['orders_no'] . '_' . time() . '_' . $idx . '.' . $ext;
        $filepath = $upload_dir . $filename;
        if (move_uploaded_file($f['tmp_name'], $filepath)) {
            $uploaded_new[] = $filename;
            $idx++;
        }
    }

    if (empty($uploaded_new)) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพได้']);
        exit;
    }

    $mode = $_POST['mode'] ?? 'append'; // 'append' or 'replace'
    if ($mode === 'replace') {
        foreach ($existing_images as $old) {
            if (file_exists($upload_dir . $old)) {
                @unlink($upload_dir . $old);
            }
        }
        $final_images = $uploaded_new;
    } else {
        $final_images = array_merge($existing_images, $uploaded_new);
    }

    $final_string = implode(',', $final_images);
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET packing_image = ?, packed_at = IFNULL(packed_at, NOW()) 
        WHERE orders_id = ?
    ");
    $updateStmt->execute([$final_string, $orders_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัปโหลดรูปพัสดุเรียบร้อยแล้ว',
        'images' => $final_images,
        'count' => count($final_images)
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
