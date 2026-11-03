<?php
require 'includes/config.php';
try {
    $db->exec("ALTER TABLE product ADD COLUMN vat_type VARCHAR(20) DEFAULT 'included'");
    echo 'Success';
} catch (Exception $e) {
    echo $e->getMessage();
}
