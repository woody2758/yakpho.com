<?php
/**
 * Set password for Owner account (user_id = 4)
 */

require_once __DIR__ . '/includes/config.php';

$user_id = 4;
$new_password = 'Admin@2024'; // Strong password
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
    $stmt->execute([$hashed, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Password updated successfully for user_id: $user_id\n";
        echo "Username: (check database)\n";
        echo "Password: $new_password\n";
        echo "\nPlease save these credentials securely!\n";
        
        // Show user info
        $user = $db->prepare("SELECT * FROM user WHERE user_id = ?");
        $user->execute([$user_id]);
        $info = $user->fetch(PDO::FETCH_ASSOC);
        
        if ($info) {
            echo "\n=== Account Info ===\n";
            echo "User ID: {$info['user_id']}\n";
            foreach ($info as $key => $val) {
                if (!in_array($key, ['user_password', 'user_del'])) {
                    echo "$key: $val\n";
                }
            }
        }
    } else {
        echo "⚠️ No user found with ID: $user_id\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
