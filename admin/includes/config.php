<?php
$db = new PDO("mysql:host=localhost;dbname=salebnet_thaiherb;charset=utf8mb4", "root", "");
$ver = "?v=".time(); // ใช้เวลาปัจจุบันเพื่อป้องกัน cache เก่า
// Backend-only config
define("ROOT_URL", "http://192.168.1.106/yakpho.com");
define("ADMIN_URL", "http://192.168.1.106/yakpho.com/admin");
define("ADMIN_ASSETS", ADMIN_URL . "/assets");

define("UPLOAD_DIR", __DIR__ . "/../../uploads");
define("SYSTEM_LOG", __DIR__ . "/../../logs");


define("ADMIN_NAME", "YakPhoAroma");

