<?php
// Clear OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache cleared successfully!<br>";
} else {
    echo "OpCache is not enabled.<br>";
}

echo "<a href='order.php'>Go to Order Page</a>";
?>
