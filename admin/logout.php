<?php
session_start();
session_destroy();
require_once __DIR__ . "/includes/config.php";
header("Location: ".ADMIN_URL."/login.html");
exit;
?>
