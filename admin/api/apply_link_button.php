<?php
$file = 'd:/xampp/htdocs/esther.rest/admin/api/get_orders_table.php';
$content = file_get_contents($file);

$target = "onclick=\"copyToClipboard('<?= \$order['orders_no'] ?>', this)\"\n                                              title=\"คัดลอกเลขที่สั่งซื้อ\">";

$replacement = "onclick=\"copyToClipboard('<?= \$order['orders_no'] ?>', this)\"\n                                              title=\"คัดลอกเลขที่สั่งซื้อ\">\n                                             <i data-lucide=\"copy\" class=\"text-muted\" style=\"width:12px; height:12px;\"></i>\n                                         </div>\n                                         <div class=\"d-flex align-items-center justify-content-center border bg-white cursor-pointer\" \n                                              style=\"width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;\" \n                                              onclick=\"copyToClipboard('<?= ROOT_URL ?>/track.php?v=<?= \$order['track_token'] ?>', this)\"\n                                              title=\"คัดลอกลิงก์ติดตามออเดอร์ให้ลูกค้า\">\n                                             <i data-lucide=\"link\" class=\"text-primary\" style=\"width:12px; height:12px;\"></i>\n                                         </div>";

// Since indentation is tricky, let's use a more flexible approach
$pattern = '/onclick="copyToClipboard\(\'<\?= \$order\[\'orders_no\'\] \?>\', this\)"\s+title="คัดลอกเลขที่สั่งซื้อ">\s+<i data-lucide="copy" class="text-muted" style="width:12px; height:12px;"><\/i>\s+<\/div>/';

$newButton = 'onclick="copyToClipboard(\'<?= $order[\'orders_no\'] ?>\', this)"
                                              title="คัดลอกเลขที่สั่งซื้อ">
                                             <i data-lucide="copy" class="text-muted" style="width:12px; height:12px;"></i>
                                         </div>
                                         <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer" 
                                              style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;" 
                                              onclick="copyToClipboard(\'<?= ROOT_URL ?>/track.php?v=<?= $order[\'track_token\'] ?>\', this)"
                                              title="คัดลอกลิงก์ติดตามออเดอร์ให้ลูกค้า">
                                             <i data-lucide="link" class="text-primary" style="width:12px; height:12px;"></i>
                                         </div>';

$content = preg_replace($pattern, $newButton, $content);

if (file_put_contents($file, $content)) {
    echo "Success: Button added.";
} else {
    echo "Error: Could not write to file.";
}
