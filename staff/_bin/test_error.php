<?php
session_start();
$_SESSION['staff_logged_in'] = true;
require 'post_world_groups.php';
