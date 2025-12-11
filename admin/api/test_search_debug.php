<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Testing User Search for 'สราวุธ' ===\n\n";

// Test 1: Show all users with their names
echo "1. All users (first 10):\n";
$stmt = $db->query("SELECT user_id, user_name, user_lastname, user_nickname FROM user WHERE user_del = 0 LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: C{$row['user_id']} | Name: {$row['user_name']} | LastName: {$row['user_lastname']} | Nickname: {$row['user_nickname']}\n";
}

// Test 2: Search for สราวุธ in user_name
echo "\n2. Search 'สราวุธ' in user_name:\n";
$stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_nickname FROM user WHERE user_del = 0 AND user_name LIKE ?");
$stmt->execute(['%สราวุธ%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found: " . count($results) . " records\n";
foreach ($results as $row) {
    echo "ID: C{$row['user_id']} | Name: {$row['user_name']} | LastName: {$row['user_lastname']} | Nickname: {$row['user_nickname']}\n";
}

// Test 3: Search for สราวุธ in user_lastname
echo "\n3. Search 'สราวุธ' in user_lastname:\n";
$stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_nickname FROM user WHERE user_del = 0 AND user_lastname LIKE ?");
$stmt->execute(['%สราวุธ%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found: " . count($results) . " records\n";
foreach ($results as $row) {
    echo "ID: C{$row['user_id']} | Name: {$row['user_name']} | LastName: {$row['user_lastname']} | Nickname: {$row['user_nickname']}\n";
}

// Test 4: Search for สราวุธ in user_nickname
echo "\n4. Search 'สราวุธ' in user_nickname:\n";
$stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_nickname FROM user WHERE user_del = 0 AND user_nickname LIKE ?");
$stmt->execute(['%สราวุธ%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found: " . count($results) . " records\n";
foreach ($results as $row) {
    echo "ID: C{$row['user_id']} | Name: {$row['user_name']} | LastName: {$row['user_lastname']} | Nickname: {$row['user_nickname']}\n";
}

// Test 5: Search for สราวุธ in ANY field
echo "\n5. Search 'สราวุธ' in ANY field (name, lastname, nickname):\n";
$stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_nickname FROM user WHERE user_del = 0 AND (user_name LIKE ? OR user_lastname LIKE ? OR user_nickname LIKE ?)");
$stmt->execute(['%สราวุธ%', '%สราวุธ%', '%สราวุธ%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found: " . count($results) . " records\n";
foreach ($results as $row) {
    echo "ID: C{$row['user_id']} | Name: {$row['user_name']} | LastName: {$row['user_lastname']} | Nickname: {$row['user_nickname']}\n";
}

echo "\n=== Current API Logic ===\n";
echo "The current get_users_list.php searches in:\n";
echo "- user_name\n";
echo "- user_lastname\n";
echo "- user_email\n";
echo "- user_mobile\n";
echo "BUT NOT in user_nickname!\n";
?>
