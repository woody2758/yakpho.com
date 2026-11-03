<?php
$db = new PDO("mysql:host=localhost;dbname=woodysay_esther;charset=utf8mb4", "woodysay_esther", "cYuekzwRgSgNNAMqJhwA");
$ver = "?v=".time(); // ใช้เวลาปัจจุบันเพื่อป้องกัน cache เก่า
// Backend-only config
define("ROOT_URL", "https://esther.rest");
define("BASE_URL", ROOT_URL);
define("URL_PATH", "https://esther.rest");
define("ADMIN_URL", ROOT_URL . "/admin");
define("ADMIN_ASSETS", ADMIN_URL . "/assets");

define("UPLOAD_DIR", __DIR__ . "/../../uploads");
define("SYSTEM_LOG", __DIR__ . "/../../logs");
require_once __DIR__ . "/functions.php";


define("ADMIN_NAME", "Esther.Rest");
