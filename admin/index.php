<?php
require __DIR__ . "/includes/config.php";   // โหลด config ก่อน (จะตรวจ remember_token ด้วย)

// ถ้าล็อกอินอยู่แล้วให้พาไปหน้า Dashboard ทันที
if (!empty($_SESSION['admin_logged'])) {
    header("Location: " . ADMIN_URL . "/dashboard.html");
    exit;
}

$page_title = "เข้าสู่ระบบ Admin";
require __DIR__ . "/includes/header.php";   // แล้วค่อยโหลด header
?>

<div class="login-wrapper">
  <div class="login-box">
    <div class="text-center mb-4">
        <img src="<?= ROOT_URL ?>/uploads/shop/Esther.Aroma.Icon.Logo.png" alt="Esther Aroma Logo" style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 10px;">
        <h3 class="login-title" style="letter-spacing: 1.5px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; font-size: 1.25rem;">ESTHER AROMA</h3>
        <div class="login-subtitle" style="color: #888; font-size: 0.85rem; margin-bottom: 15px;">Admin Control Panel</div>
    </div>

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

      <!-- Remember Me Checkbox (1 Year) -->
      <div class="mb-3 d-flex align-items-center">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" value="1" checked>
          <label class="form-check-label" for="rememberMe">
            จำฉันไว้ (1 ปี)
          </label>
        </div>
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

<?php include __DIR__ . "/includes/footer.php"; ?>
