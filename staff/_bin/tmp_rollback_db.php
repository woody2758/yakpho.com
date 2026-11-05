<?php
require 'd:/xampp/htdocs/yakpho.com/staff/db.php';
try { $db->exec("ALTER TABLE creative_contents DROP COLUMN campaign_tag"); echo "Dropped from creative_contents\n"; } catch(Exception $e){}
try { $db->exec("ALTER TABLE facebook_groups DROP COLUMN campaign_tag"); echo "Dropped from facebook_groups\n"; } catch(Exception $e){}
try { $db->exec("ALTER TABLE creative_post_queue DROP COLUMN campaign_tag"); echo "Dropped from creative_post_queue\n"; } catch(Exception $e){}
