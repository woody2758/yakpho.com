<?php
require 'd:/xampp/htdocs/yakpho.com/staff/db.php';
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN post_ready TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e){}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN strategy_marker VARCHAR(20) NOT NULL DEFAULT 'Observe'"); } catch(Exception $e){}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN campaign_tag VARCHAR(255)"); } catch(Exception $e){}
