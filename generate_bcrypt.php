<?php
// Generate bcrypt hash for yp123456
$password = "yp123456";

// Using PASSWORD_BCRYPT with cost 10 (same as in reset_password_all.php)
$hash_bcrypt_cost10 = password_hash($password, PASSWORD_BCRYPT, ["cost" => 10]);

// Using PASSWORD_DEFAULT (current default is usually bcrypt)
$hash_default = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n\n";
echo "Bcrypt Hash (cost 10):\n";
echo $hash_bcrypt_cost10 . "\n\n";
echo "Password Default Hash:\n";
echo $hash_default . "\n\n";

// Verify the hash works
if (password_verify($password, $hash_bcrypt_cost10)) {
    echo "✓ Verification successful for bcrypt cost 10\n";
}

if (password_verify($password, $hash_default)) {
    echo "✓ Verification successful for default hash\n";
}
?>
