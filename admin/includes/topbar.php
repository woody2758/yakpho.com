<?php
// topbar.php – YakPho Premium Topbar

// ค่าเริ่มต้นเผื่อไว้ (ปกติ auth.php เตรียมให้แล้ว)
$ADMIN_ID    = $ADMIN_ID    ?? ($_SESSION['admin_id']    ?? 0);
$ADMIN_NAME  = $ADMIN_NAME  ?? ($_SESSION['admin_name']  ?? "ผู้ดูแลระบบ");
$ADMIN_ROLE  = $ADMIN_ROLE  ?? ($_SESSION['admin_role']  ?? "");
$ADMIN_PIC   = $_SESSION['admin_picture'] ?? "default.png";

// สร้าง path รูปโปรไฟล์ (HTTP)
$profilePath = ROOT_URL . "/assets/img/profile/" . ($ADMIN_ID ?: "default") . "/" . $ADMIN_PIC;

// เช็คไฟล์จริงในเครื่อง
$profileFile = __DIR__ . "/../../assets/img/profile/" . ($ADMIN_ID ?: "default") . "/" . $ADMIN_PIC;
if (!is_file($profileFile)) {
    $profilePath = ROOT_URL . "/assets/img/profile/default.png";
}
?>
<div class="topbar">

    <!-- Left: Sidebar Toggle -->
    <div class="topbar-left">
        <button class="btn-sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <i data-lucide="menu"></i>
        </button>
    </div>

    <!-- Right: Settings + Profile -->
    <div class="topbar-right">

        <?php if ($ADMIN_ROLE === "owner" || $ADMIN_ROLE === "admin"): ?>
            <button class="btn-topbar-icon"
                    onclick="location.href='<?= ADMIN_URL ?>/settings.php'"
                    aria-label="Settings">
                <i data-lucide="settings"></i>
            </button>
        <?php endif; ?>

        <!-- Profile -->
        <div class="profile-wrapper">

            <img src="<?= htmlspecialchars($profilePath) ?>"
                 class="profile-pic"
                 alt="profile image"
                 onclick="toggleProfileMenu()">

            <div class="profile-dropdown" id="profileMenu">

                <div class="pd-info">
                    <div class="pd-name"><?= htmlspecialchars($ADMIN_NAME) ?></div>
                    <div class="pd-role">Role: <?= htmlspecialchars($ADMIN_ROLE ?: "-") ?></div>
                </div>

                <a href="<?= ADMIN_URL ?>/profile.php" class="pd-item">
                    <i data-lucide="user"></i>
                    <span>แก้ไขข้อมูลส่วนตัว</span>
                </a>

                <a href="<?= ADMIN_URL ?>/profile-picture.php" class="pd-item">
                    <i data-lucide="image"></i>
                    <span>เปลี่ยนรูปโปรไฟล์</span>
                </a>

                <div class="pd-item logout"
                     onclick="location.href='<?= ADMIN_URL ?>/logout.php'">
                    <i data-lucide="log-out"></i>
                    <span>ออกจากระบบ</span>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
function toggleProfileMenu() {
    document.getElementById("profileMenu").classList.toggle("open");
}

// ปิด dropdown เมื่อคลิกรอบๆ
document.addEventListener("click", function(e) {
    const menu = document.getElementById("profileMenu");
    const avatar = document.querySelector(".profile-pic");

    if (!menu.contains(e.target) && !avatar.contains(e.target)) {
        menu.classList.remove("open");
    }
});
</script>


<script>
function toggleProfileMenu() {
    const menu = document.getElementById("profileMenu");
    if (!menu) return;
    menu.classList.toggle("open");
}

document.addEventListener('click', function (e) {
    const menu    = document.getElementById("profileMenu");
    const wrapper = document.querySelector(".profile-wrapper");
    if (!menu || !wrapper) return;

    if (!wrapper.contains(e.target)) {
        menu.classList.remove("open");
    }
});
</script>
