<?php
if (!isset($page_id)) {
echo "<h2>ไม่พบสินค้า</h2>";
return;
}
$stmt=$db->prepare("SELECT * FROM product WHERE product_id=?");
$stmt->execute([$page_id]);
$product=$stmt->fetch(PDO::FETCH_ASSOC);


if(!$product){ echo "<h2>ไม่พบสินค้า</h2>"; return; }
?>
<h1><?= $product['name'] ?></h1>
<p><?= $product['detail'] ?></p>