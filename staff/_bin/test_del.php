<?php
require 'd:/xampp/htdocs/yakpho.com/staff/db.php';
try {
    $_GET['del_img'] = 9;
    require 'd:/xampp/htdocs/yakpho.com/staff/creative_manage.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} catch (Error $e) {
    echo "Fatal: " . $e->getMessage();
}
