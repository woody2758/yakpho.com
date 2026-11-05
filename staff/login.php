<?php
require_once "db.php";

// If already logged in, redirect to dashboard
if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $email_value = htmlspecialchars($email);

    if (empty($email) || empty($password)) {
        $error = 'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน';
    } else {
        $stmt = $db->prepare("SELECT * FROM staff_admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Check status
            if ($user['status'] === 'deleted') {
                $error = 'บัญชีนี้ถูกเข้าสู่ระบบไม่ได้ (Deleted)';
            } elseif ($user['status'] === 'hold') {
                $error = 'บัญชีนี้ถูกระงับการใช้งานชั่วคราว (Hold)';
            } else {
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Success setup session
                    $_SESSION['staff_logged_in'] = true;
                    $_SESSION['staff_id'] = $user['id'];
                    $_SESSION['staff_name'] = $user['name'];
                    $_SESSION['staff_role'] = $user['role'];
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            }
        } else {
            $error = 'ไม่พบบัญชีผู้ใช้นี้ในระบบ';
        }
    }
}
?>
<!doctype html>
<html lang="th">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Login - Esther Aroma Staff</title>
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Kanit', sans-serif; background-color: #f8f9fa; }
    </style>
  </head>
  <body class=" d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="." class="navbar-brand navbar-brand-autodark">
             <h1 class="m-0 text-primary">Yakpho <span class="text-dark">Staff</span></h1>
          </a>
        </div>
        
        <form class="card card-md shadow-sm border-0" action="login.php" method="post" autocomplete="off">
          <div class="card-body">
            <h2 class="card-title text-center mb-4">เข้าสู่ระบบจัดการข้อมูล AI</h2>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
              <div class="d-flex">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div><?= $error ?></div>
              </div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?= $email_value ?>" required autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group input-group-flat">
                <input type="password" name="password" id="password-input" class="form-control" placeholder="Your password" required autocomplete="off">
                <span class="input-group-text" style="cursor:pointer;" id="toggle-password" title="Show/Hide password">
                  <!-- Eye Open Icon -->
                  <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2" /><path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" /></svg>
                  <!-- Eye Closed Icon (hidden by default) -->
                  <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" class="icon text-muted d-none" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="3" y1="3" x2="21" y2="21" /><path d="M10.584 10.587a2 2 0 0 0 2.828 2.829" /><path d="M9.363 5.365a9.466 9.466 0 0 1 2.637 -.365c4 0 7.333 2.333 10 7c-.778 1.361 -1.612 2.524 -2.503 3.488m-2.14 1.861c-1.631 1.1 -3.415 1.651 -5.357 1.651c-4 0 -7.333 -2.333 -10 -7c1.45 -2.54 3.066 -4.449 4.85 -5.727" /></svg>
                </span>
              </div>
            </div>
            <script>
              document.getElementById('toggle-password').addEventListener('click', function() {
                const input = document.getElementById('password-input');
                const eyeOpen = document.getElementById('eye-icon');
                const eyeOff = document.getElementById('eye-off-icon');
                if (input.type === 'password') {
                  input.type = 'text';
                  eyeOpen.classList.add('d-none');
                  eyeOff.classList.remove('d-none');
                } else {
                  input.type = 'password';
                  eyeOpen.classList.remove('d-none');
                  eyeOff.classList.add('d-none');
                }
              });
            </script>
            <div class="form-footer">
              <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </div>
          </div>
        </form>
        <div class="text-center text-muted mt-3">
          Esther Aroma Backend System
        </div>
      </div>
    </div>
  </body>
</html>
