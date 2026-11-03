<?php
$currentLang = $SITE['lang'] ?? 'th';
$c_code = htmlspecialchars($_GET['c'] ?? '');
$token = htmlspecialchars($_GET['token'] ?? '');
?>

<section class="section-pad bg-surface-secondary" id="vipPortalSection">
    <div class="anona-container-narrow">
        
        <!-- Loading State Card -->
        <div id="vipLoadingCard" class="checkout-card-box text-center py-5" data-aos="fade-up">
            <div class="spinner-border text-gold mb-3" role="status"></div>
            <h2 class="section-main-title"><?= $currentLang === 'th' ? 'กำลังตรวจสอบสิทธิ์ VIP ของท่าน...' : 'Verifying your VIP membership...' ?></h2>
            <p class="section-subtitle"><?= $currentLang === 'th' ? 'ระบบกำลังเตรียมข้อมูลและคำนวณแต้มสะสม กรุณารอสักครู่' : 'Retrieving your purchase history and reward points...' ?></p>
        </div>

        <!-- Invalid / Expired State Card -->
        <div id="vipErrorCard" class="checkout-card-box text-center py-5 d-none" data-aos="fade-up">
            <div class="mb-3 text-gold">
                <i data-lucide="shield-alert" width="48" height="48"></i>
            </div>
            <h2 class="section-main-title"><?= $currentLang === 'th' ? 'ลิงก์สิทธิพิเศษหมดอายุหรือไม่ถูกต้อง' : 'Invalid or Expired VIP Link' ?></h2>
            <p class="section-subtitle mb-4" id="vipErrorMessage">
                <?= $currentLang === 'th'
                    ? 'ลิงก์สิทธิพิเศษนี้อาจหมดอายุแล้ว (เกิน 30 วัน) หรือรหัสไม่ถูกต้อง ท่านสามารถติดต่อแอดมินทาง LINE หรือข้อความเพื่อขอรับลิงก์ใหม่ได้ทันทีครับ'
                    : 'This VIP invitation link may have expired or is invalid. Please contact our support team via LINE or Messenger to receive a new link.' ?>
            </p>
            <a href="https://line.me/ti/p/~@yakpho" target="_blank" rel="noopener" class="btn-anona-primary">
                <i data-lucide="message-circle" width="18" height="18"></i>
                <span><?= $currentLang === 'th' ? 'ติดต่อแอดมินเพื่อขอรับลิงก์ใหม่' : 'Contact Support via LINE' ?></span>
            </a>
        </div>

        <!-- Active VIP Member Portal Card -->
        <div id="vipActiveCard" class="d-none">
            
            <!-- 1. Heritage Welcome Banner -->
            <div class="checkout-card-box mb-4 text-center" data-aos="fade-up">
                <span class="section-eyebrow">EXCLUSIVE VIP PORTAL • YAK PHO HERBAL WELLNESS</span>
                <div class="section-script-title">Welcome Back</div>
                <h1 class="section-main-title mb-2">
                    <?= $currentLang === 'th' ? 'ยินดีต้อนรับท่านสมาชิกระดับ VIP' : 'Welcome to Yak Pho VIP Club' ?>
                </h1>
                <p class="section-subtitle mb-4">
                    <span id="vipGreetingText"></span>
                </p>
                
                <!-- VIP Badge Grid -->
                <div class="grid-3-cols mb-3">
                    <div class="p-3 bg-surface-primary rounded-lg border-subtle">
                        <span class="text-muted small d-block"><?= $currentLang === 'th' ? 'รหัสสมาชิกประจำตัว' : 'Member ID' ?></span>
                        <strong class="text-gold h4 mb-0" id="vipMemberCode">--</strong>
                    </div>
                    <div class="p-3 bg-surface-primary rounded-lg border-subtle">
                        <span class="text-muted small d-block"><?= $currentLang === 'th' ? 'คำสั่งซื้อสะสม' : 'Total Orders' ?></span>
                        <strong class="text-forest h4 mb-0" id="vipOrderCount">0 ครั้ง</strong>
                    </div>
                    <div class="p-3 bg-surface-primary rounded-lg border-subtle">
                        <span class="text-muted small d-block"><?= $currentLang === 'th' ? 'แต้มสะสมพร้อมใช้' : 'Reward Points' ?></span>
                        <strong class="text-gold h4 mb-0" id="vipPoints">0 แต้ม</strong>
                    </div>
                </div>

                <div class="text-muted small">
                    <i data-lucide="calendar" width="14" height="14"></i>
                    <span><?= $currentLang === 'th' ? 'เป็นลูกค้าคนสำคัญกับเราตั้งแต่:' : 'Valued Customer Since:' ?></span>
                    <strong id="vipMemberSince">--</strong>
                </div>
            </div>

            <!-- 2. Profile & Shipping Address Update Form -->
            <div class="checkout-card-box" data-aos="fade-up" data-aos-delay="50">
                <div class="section-header-center mb-4">
                    <span class="section-eyebrow"><?= $currentLang === 'th' ? 'อัปเดตข้อมูลเพื่อรับสิทธิประโยชน์' : 'UPDATE VIP PROFILE' ?></span>
                    <h2 class="section-main-title"><?= $currentLang === 'th' ? 'ข้อมูลสมาชิก & ที่อยู่จัดส่ง (ทั้งในและต่างประเทศ)' : 'Shipping Address & Contact Info' ?></h2>
                    <p class="text-muted small">
                        <?= $currentLang === 'th'
                            ? 'กรุณาตรวจสอบและอัปเดตข้อมูลเพื่อความสะดวกรวดเร็วในการจัดส่งสินค้า และการสะสมแต้มในทุกคำสั่งซื้อ'
                            : 'Please review and update your delivery details to ensure swift international shipping and accurate reward points.' ?>
                    </p>
                </div>

                <form id="vipProfileForm">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="c" value="<?= $c_code ?>">
                    <input type="hidden" name="token" value="<?= $token ?>">

                    <div class="grid-2-cols mb-3">
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'ชื่อจริง *' : 'First Name *' ?></label>
                            <input type="text" name="name" id="inp_name" class="form-input-anona" required>
                        </div>
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'นามสกุล' : 'Last Name' ?></label>
                            <input type="text" name="lastname" id="inp_lastname" class="form-input-anona">
                        </div>
                    </div>

                    <div class="grid-2-cols mb-3">
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'ชื่อร้านนวด / สปา / บริษัท (ถ้ามี)' : 'Spa / Shop / Business Name (Optional)' ?></label>
                            <input type="text" name="shop_name" id="inp_shop_name" class="form-input-anona" placeholder="<?= $currentLang === 'th' ? 'เช่น สยามอโรม่าสปา, Best Thai Massage' : 'e.g. Siam Aroma Spa' ?>">
                        </div>
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'เบอร์โทรศัพท์ / WhatsApp *' : 'Phone / WhatsApp *' ?></label>
                            <input type="tel" name="mobile" id="inp_mobile" class="form-input-anona" required>
                        </div>
                    </div>

                    <div class="grid-2-cols mb-3">
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'อีเมล' : 'Email Address' ?></label>
                            <input type="email" name="email" id="inp_email" class="form-input-anona" placeholder="name@example.com">
                        </div>
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'ประเทศปลายทางจัดส่ง *' : 'Destination Country *' ?></label>
                            <select name="country_id" id="inp_country_id" class="form-select-anona" required>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <div class="form-group-anona mb-3">
                        <label class="form-label-anona"><?= $currentLang === 'th' ? 'ที่อยู่สำหรับจัดส่ง (Address Line 1) *' : 'Delivery Address (Line 1) *' ?></label>
                        <input type="text" name="address" id="inp_address" class="form-input-anona" required placeholder="<?= $currentLang === 'th' ? 'บ้านเลขที่, ถนน, อาคาร, แขวง/ตำบล' : 'House no, Street name, Building' ?>">
                    </div>

                    <div class="grid-2-cols mb-3">
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'เมือง / เขต / จังหวัด (City / State / Province)' : 'City / State / Province' ?></label>
                            <input type="text" name="address2" id="inp_address2" class="form-input-anona" placeholder="<?= $currentLang === 'th' ? 'เช่น เมือง Zurich, เขตคลองเตย' : 'e.g. Zurich, Berlin, Bangkok' ?>">
                        </div>
                        <div class="form-group-anona">
                            <label class="form-label-anona"><?= $currentLang === 'th' ? 'รหัสไปรษณีย์ (Postal Code / ZIP)' : 'Postal Code / ZIP' ?></label>
                            <input type="text" name="postcode" id="inp_postcode" class="form-input-anona">
                        </div>
                    </div>

                    <div class="form-group-anona mb-4">
                        <label class="form-label-anona"><?= $currentLang === 'th' ? 'กำหนดรหัสผ่านเข้าสู่ระบบ (เว้นว่างไว้ได้)' : 'Set Account Password (Optional)' ?></label>
                        <input type="password" name="password" id="inp_password" class="form-input-anona" placeholder="<?= $currentLang === 'th' ? 'ตั้งรหัสผ่านสำหรับเข้าสู่ระบบด้วยตนเองในครั้งถัดไป' : 'Set a password for direct login next time' ?>">
                        <small class="text-muted d-block mt-1">
                            <?= $currentLang === 'th' ? '* หากไม่ต้องการตั้งรหัสผ่าน สามารถเข้าสู่ระบบด้วยเบอร์โทรศัพท์หรือลิงก์ VIP ได้เช่นเดิม' : '* You can always sign in via phone number or your private VIP link.' ?>
                        </small>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-between align-center pt-3 border-top-subtle">
                        <button type="submit" class="btn-anona-primary" id="btnSaveVip">
                            <i data-lucide="check-circle" width="18" height="18"></i>
                            <span><?= $currentLang === 'th' ? 'บันทึกข้อมูลสมาชิก VIP' : 'Save & Activate VIP Profile' ?></span>
                        </button>
                        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="btn-anona-outline">
                            <i data-lucide="shopping-bag" width="18" height="18"></i>
                            <span><?= $currentLang === 'th' ? 'เข้าสู่ร้านค้า ช้อปสินค้าต่อ' : 'Explore Spa Catalog' ?></span>
                        </a>
                    </div>
                </form>
            </div>

        </div>

    </div>
</section>

<!-- Include script logic via external listener in anona-esther.js or inline trigger -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const cCode = "<?= $c_code ?>";
    const token = "<?= $token ?>";
    const currentLang = "<?= $currentLang ?>";
    
    if (!cCode || !token) {
        document.getElementById("vipLoadingCard").classList.add("d-none");
        document.getElementById("vipErrorCard").classList.remove("d-none");
        return;
    }

    // Call API to validate
    fetch("<?= URL_PATH ?>/api/vip_customer.php?action=validate_token&c=" + encodeURIComponent(cCode) + "&token=" + encodeURIComponent(token))
        .then(res => res.json())
        .then(res => {
            document.getElementById("vipLoadingCard").classList.add("d-none");
            
            if (res.status !== 'success') {
                document.getElementById("vipErrorCard").classList.remove("d-none");
                if (res.message) {
                    document.getElementById("vipErrorMessage").textContent = res.message;
                }
                return;
            }

            // Populate data
            const cust = res.customer;
            document.getElementById("vipActiveCard").classList.remove("d-none");
            
            const fullName = (cust.name + " " + cust.lastname).trim() || (cust.shop_name || "ท่านสมาชิกคนสำคัญ");
            document.getElementById("vipGreetingText").textContent = currentLang === 'th' 
                ? "ขอขอบพระคุณ " + fullName + " ที่ให้ความไว้วางใจในผลิตภัณฑ์สปาและสมุนไพรยักษ์โพธิ์มาโดยตลอด"
                : "Thank you, " + fullName + ", for your continued trust in Yak Pho Aroma therapeutic products.";

            document.getElementById("vipMemberCode").textContent = cust.c_code;
            document.getElementById("vipOrderCount").textContent = cust.order_count + (currentLang === 'th' ? " ครั้ง" : " Orders");
            document.getElementById("vipPoints").textContent = cust.points.toLocaleString() + (currentLang === 'th' ? " แต้ม" : " Pts");
            document.getElementById("vipMemberSince").textContent = cust.member_since;

            // Form inputs
            document.getElementById("inp_name").value = cust.name || "";
            document.getElementById("inp_lastname").value = cust.lastname || "";
            document.getElementById("inp_shop_name").value = cust.shop_name || "";
            document.getElementById("inp_mobile").value = cust.mobile || "";
            document.getElementById("inp_email").value = cust.email || "";
            document.getElementById("inp_address").value = cust.address || "";
            document.getElementById("inp_address2").value = cust.address2 || "";
            document.getElementById("inp_postcode").value = cust.postcode || "";

            // Populate countries
            const countrySelect = document.getElementById("inp_country_id");
            countrySelect.innerHTML = "";
            res.countries.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.country_id;
                opt.textContent = c.country_name + " (" + c.country_code + ")";
                if (parseInt(c.country_id) === parseInt(cust.country_id)) {
                    opt.selected = true;
                }
                countrySelect.appendChild(opt);
            });

            if (window.lucide) {
                window.lucide.createIcons();
            }
        })
        .catch(err => {
            document.getElementById("vipLoadingCard").classList.add("d-none");
            document.getElementById("vipErrorCard").classList.remove("d-none");
        });

    // Handle form submission
    const form = document.getElementById("vipProfileForm");
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        const btn = document.getElementById("btnSaveVip");
        btn.disabled = true;

        const formData = new FormData(form);
        fetch("<?= URL_PATH ?>/api/vip_customer.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: currentLang === 'th' ? 'อัปเดตข้อมูลสำเร็จ!' : 'Profile Updated!',
                    text: res.message,
                    confirmButtonColor: '#0B3B35',
                    confirmButtonText: currentLang === 'th' ? 'เข้าสู่ร้านค้า' : 'Shop Now'
                }).then(() => {
                    if (res.redirect) {
                        window.location.href = res.redirect;
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: currentLang === 'th' ? 'เกิดข้อผิดพลาด' : 'Error',
                    text: res.message || 'Error occurred',
                    confirmButtonColor: '#0B3B35'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message,
                confirmButtonColor: '#0B3B35'
            });
        });
    });
});
</script>

