<?php
// ดึงข้อมูลสินค้า (เอาเฉพาะที่เปิดขาย)
$sql = "SELECT product_id, name, picture FROM product ORDER BY product_id DESC";
$stmt = $db->query($sql);

?>

<div class="container py-4">

    <h1 class="mb-4"><?= $LANG['products'] ?></h1>

    <div class="row g-4">

        <?php while($p = $stmt->fetch()): ?>

            <?php
                // path รูปสินค้า
                $img_file = $p['picture'];
                $img_path = URL_PATH . "/images/product/" . $img_file;

                // ถ้าไฟล์ไม่เจอให้ใช้รูป noimage.webp
                if (!file_exists(__DIR__ . "/../images/product/" . $img_file)) {
                    $img_path = URL_PATH . "/assets/img/noimage.webp";
                }
            ?>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= URL_PATH ?>/<?= $lang ?>/product/<?= $p['product_id'] ?>" 
                   class="text-decoration-none text-dark">

                    <div class="card shadow-sm product-card">
                        <img src="<?= $img_path ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">

                        <div class="card-body text-center">
                            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>

                            <button class="btn btn-primary btn-sm mt-2">
                                <?= ($lang == 'th' ? 'ดูสินค้า' : 'View Product') ?>
                            </button>
                        </div>
                    </div>

                </a>
            </div>

        <?php endwhile; ?>

    </div>
</div>
