<?php
$page_title = "เข้าสู่ระบบ Admin";
require "includes/config.php";   // โหลด config ก่อน
require "includes/header.php";   // แล้วค่อยโหลด header
?>

<div class="login-wrapper">
  <div class="login-box">
    <h3 class="login-title">YakPho Admin</h3>
    <div class="login-subtitle">เข้าสู่ระบบเพื่อจัดการสินค้าและคำสั่งซื้อ</div>

    <form action="login.php" method="post">

      <!-- Email -->
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="text" name="email" class="form-control"
               value="<?= $_SESSION['old_identity'] ?? '' ?>" autofocus>
      </div>

      <!-- Password + Toggle Eye -->
      <div class="mb-3" style="position: relative;">
        <label class="form-label">รหัสผ่าน</label>

        <input type="password" name="password" id="password"
               class="form-control" autocomplete="off">

        <span id="togglePassword"
          style="position:absolute; right:12px; top:38px; cursor:pointer; font-size:18px; color:#999;">
          👁️
        </span>
      </div>

      <button id="loginBtn" class="btn btn-yakpho w-100 mt-2">เข้าสู่ระบบ</button>
    </form>
  </div>
</div>

<script>
// ปุ่ม Loading ตอนกด Login
document.querySelector("form").addEventListener("submit", function() {
    const btn = document.getElementById("loginBtn");
    btn.classList.add("btn-loading");
    btn.disabled = true;
    btn.textContent = "กำลังเข้าสู่ระบบ...";
});

// Toggle Password Eye
document.getElementById("togglePassword").addEventListener("click", function () {
    const input = document.getElementById("password");
    const type = input.getAttribute("type") === "password" ? "text" : "password";
    input.setAttribute("type", type);
    this.textContent = type === "password" ? "👁️" : "🙈";
});
</script>

<?php include "includes/footer.php"; ?>
