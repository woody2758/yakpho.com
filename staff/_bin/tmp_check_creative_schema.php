<?php
require 'd:/xampp/htdocs/yakpho.com/staff/db.php';
$tables = ['creative_images', 'creative_contents'];
foreach($tables as $t) {
    echo "Table $t:\n";
    $res = $db->query("DESCRIBE $t");
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'].' ('.$row['Type'].")\n";
    }
    echo "\n";
}
?>
