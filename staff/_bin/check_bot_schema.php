<?php
require_once "db.php";
try {
    $stmt = $db->query("DESCRIBE bot_processes");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
} catch (Exception $e) {
    echo $e->getMessage();
}
