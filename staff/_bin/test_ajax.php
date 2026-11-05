<?php
session_start();
$_POST['ajax_action'] = 'load_queues';
require 'post_world_groups.php';
