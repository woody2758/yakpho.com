<?php
if (!isset($page_title)) { 
    $page_title = "YakPho Admin"; 
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> - YakPho Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Admin CSS -->
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin.css<?= $ver ?>">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/layout.css<?= $ver ?>">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/components.css<?= $ver ?>">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/login.css<?= $ver ?>">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/sweetalert.css<?= $ver ?>">

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php
// แสดง Alert ถ้ามี
if (!empty($_SESSION['alert'])) {
    [$alertType, $alertMsg] = $_SESSION['alert'];
    unset($_SESSION['alert']);
    ?>
    <script>
    Swal.fire({
        icon: '<?= $alertType ?>',
        title: '<?= addslashes($alertMsg) ?>',
        // text: '', // ถ้าต้องการใส่รายละเอียดเพิ่มอีกบรรทัด
        customClass: {
            popup: 'yakpho-swal-popup',
            title: 'yakpho-swal-title',
            htmlContainer: 'yakpho-swal-html',
            confirmButton: 'yakpho-swal-confirm',
            cancelButton: 'yakpho-swal-cancel'
        },
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#000'
    }).then(() => {
        <?php if ($alertType === 'success'): ?>
        // ถ้าต้องการ redirect หลัง success เช่น login
        window.location.href = "<?= ADMIN_URL ?>/dashboard.html";
        <?php endif; ?>
    });
    </script>
    <?php
}
?>
