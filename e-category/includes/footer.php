<?php
// ===========================================================
// 💎 Yakpho Aroma Footer (Final + Cache Busting Version)
// ===========================================================
$ver = time(); // ใช้เวลาปัจจุบันเพื่อป้องกัน cache เก่า
?>



<!-- 🔊 เสียงคลิก -->
<audio id="softClick" src="assets/sounds/soft-click.wav" preload="auto"></audio>
<script src="<?= URL_PATH ?>assets/js/app.js?v=1.1<?php echo $ver;?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
