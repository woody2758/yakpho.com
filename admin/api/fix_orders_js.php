<?php
$f = 'd:/xampp/htdocs/esther.rest/admin/assets/js/orders.js';
$c = file_get_contents($f);

$search = "                document.getElementById('customerUserId').value = user.user_id;
                document.getElementById('customerName').value = user.user_name;
                document.getElementById('customerLastname').value = user.user_lastname;
                document.getElementById('customerMobile').value = user.user_mobile;
                document.getElementById('customerEmail').value = user.user_email;";

$replace = "                document.getElementById('customerUserId').value = user.user_id;
                document.getElementById('customerName').value = user.user_name || '';
                document.getElementById('customerLastname').value = user.user_lastname || '';
                
                const elNickname = document.getElementById('customerNickname');
                if(elNickname) elNickname.value = user.user_nickname || '';
                
                document.getElementById('customerMobile').value = user.user_mobile || '';
                document.getElementById('customerEmail').value = user.user_email || '';
                
                const elNote = document.getElementById('customerNote');
                if(elNote) elNote.value = user.user_note || '';
                
                const elMap = document.getElementById('customerGoogleMap');
                if(elMap) elMap.value = user.user_google_map || '';

                const btnOpenMap = document.getElementById('btnOpenMap');
                if (btnOpenMap) {
                    if (user.user_google_map && user.user_google_map.trim() !== '') {
                        btnOpenMap.href = user.user_google_map;
                        btnOpenMap.style.display = 'inline-flex';
                        btnOpenMap.style.alignItems = 'center';
                    } else {
                        btnOpenMap.style.display = 'none';
                        btnOpenMap.href = '#';
                    }
                }
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }";

// normalize line endings
$c = str_replace("\r\n", "\n", $c);
$search = str_replace("\r\n", "\n", $search);
$replace = str_replace("\r\n", "\n", $replace);

if (strpos($c, $search) !== false) {
    $c = str_replace($search, $replace, $c);
    file_put_contents($f, $c);
    echo "Done!";
} else {
    echo "Not found!";
}
?>
