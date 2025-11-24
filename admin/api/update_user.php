<?php
header('Content-Type: application/json');

// Include Admin Config
require_once __DIR__ . "/../includes/config.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permission (Staff or higher)
$current_role = $_SESSION['admin_role'] ?? '';
$role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];

if (!isset($role_hierarchy[$current_role]) || $role_hierarchy[$current_role] > 5) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Function to resize and crop image to 200x200 (center crop)
function resizeAndCropImage($source_path, $destination_path, $size = 200) {
    // Get image info
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        return false;
    }

    list($orig_width, $orig_height, $image_type) = $image_info;

    // Create image resource based on type
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }

    if (!$source_image) {
        return false;
    }

    // Calculate crop dimensions (center crop to square)
    $crop_size = min($orig_width, $orig_height);
    $crop_x = ($orig_width - $crop_size) / 2;
    $crop_y = ($orig_height - $crop_size) / 2;

    // Create destination image
    $dest_image = imagecreatetruecolor($size, $size);

    // Preserve transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($dest_image, false);
        imagesavealpha($dest_image, true);
        $transparent = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
        imagefilledrectangle($dest_image, 0, 0, $size, $size, $transparent);
    }

    // Resize and crop (center crop)
    imagecopyresampled(
        $dest_image,
        $source_image,
        0, 0,                    // Destination x, y
        $crop_x, $crop_y,        // Source x, y (centered)
        $size, $size,            // Destination width, height
        $crop_size, $crop_size   // Source width, height (square crop)
    );

    // Save based on original type
    $result = false;
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dest_image, $destination_path, 90);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($dest_image, $destination_path, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dest_image, $destination_path);
            break;
    }

    // Free memory
    imagedestroy($source_image);
    imagedestroy($dest_image);

    return $result;
}

// Handle file upload or JSON data
$user_id = null;
$first_name = null;
$last_name = null;
$nickname = null;
$email = null;
$mobile = null;
$uploaded_file = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's a file upload (multipart/form-data)
    if (isset($_FILES['user_picture']) && $_FILES['user_picture']['error'] === UPLOAD_ERR_OK) {
        // Form data with file
        $user_id = (int)$_POST['user_id'];
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $nickname = $_POST['nickname'] ?? '';
        $email = $_POST['email'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $uploaded_file = $_FILES['user_picture'];
    } else {
        // JSON data without file
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            $user_id = (int)($data['user_id'] ?? 0);
            $first_name = $data['first_name'] ?? '';
            $last_name = $data['last_name'] ?? '';
            $nickname = $data['nickname'] ?? '';
            $email = $data['email'] ?? '';
            $mobile = $data['mobile'] ?? '';
        } else {
            // Regular POST data
            $user_id = (int)($_POST['user_id'] ?? 0);
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $nickname = $_POST['nickname'] ?? '';
            $email = $_POST['email'] ?? '';
            $mobile = $_POST['mobile'] ?? '';
        }
    }
}

// Validate input
if (!$user_id || !$first_name) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate that at least email or mobile is provided
if (empty($email) && empty($mobile)) {
    echo json_encode(['success' => false, 'message' => 'Either email or mobile number is required']);
    exit;
}

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate mobile format if provided (basic validation)
if (!empty($mobile) && !preg_match('/^[0-9]{9,10}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number format (9-10 digits)']);
    exit;
}

try {
    // Check if user exists
    $stmt = $db->prepare("SELECT user_id, user_picture FROM user WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $new_picture = $user['user_picture']; // Keep existing picture by default

    // Handle file upload
    if ($uploaded_file) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($uploaded_file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed']);
            exit;
        }

        if ($uploaded_file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB']);
            exit;
        }

        // Create directory if not exists
        $upload_dir = __DIR__ . "/../../assets/img/profile/{$user_id}/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('profile_') . '.jpg'; // Always save as JPG for consistency
        $upload_path = $upload_dir . $new_filename;

        // Resize and crop image to 200x200
        if (resizeAndCropImage($uploaded_file['tmp_name'], $upload_path, 200)) {
            $new_picture = $new_filename;

            // Save to profile_pic history
            $history_stmt = $db->prepare("INSERT INTO profile_pic (user_id, user_picture) VALUES (:user_id, :picture)");
            $history_stmt->execute([
                ':user_id' => $user_id,
                ':picture' => $new_filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to process image']);
            exit;
        }
    }

    // Update user information
    $update_stmt = $db->prepare("
        UPDATE user 
        SET user_name = :first_name,
            user_lastname = :last_name,
            user_nickname = :nickname,
            user_email = :email,
            user_mobile = :mobile,
            user_picture = :picture,
            updated_at = NOW()
        WHERE user_id = :user_id
    ");

    $update_stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':nickname' => $nickname,
        ':email' => $email,
        ':mobile' => $mobile,
        ':picture' => $new_picture,
        ':user_id' => $user_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
        'user' => [
            'user_id' => $user_id,
            'user_name' => $first_name,
            'user_lastname' => $last_name,
            'user_nickname' => $nickname,
            'user_email' => $email,
            'user_mobile' => $mobile,
            'user_picture' => $new_picture
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
