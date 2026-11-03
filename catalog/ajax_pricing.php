<?php
include __DIR__ . "/includes/config.php";
include __DIR__ . "/includes/api_helper.php";

$pricingRes = fetch_esther_api('pricing_content.php', ['lang' => CURRENT_LANG]);

$pricingIntro = $pricingRes ? ($pricingRes['data']['intro'] ?? []) : [];
$pricingTiers = $pricingRes ? ($pricingRes['data']['tiers'] ?? []) : [];
$pricingFeatures = $pricingRes ? ($pricingRes['data']['features'] ?? []) : [];

$p_title = !empty($pricingIntro['title']) ? $pricingIntro['title'] : 'เรทราคาส่ง YakPho Aroma';
$p_subtitle = !empty($pricingIntro['subtitle']) ? $pricingIntro['subtitle'] : 'ราคาต่อกิโลกรัม สำหรับทุกสูตรและทุกกลิ่น';

// Fallback logic if DB is empty
if (empty($pricingTiers) && CURRENT_LANG === 'th') {
    $pricingTiers = [
        ['volume' => '1 กก.', 'price' => '690 บาท'],
        ['volume' => '6 กก.', 'price' => '590 บาท'],
        ['volume' => '10 กก.', 'price' => '560 บาท'],
        ['volume' => '20 กก.', 'price' => '530 บาท'],
        ['volume' => '30 กก.', 'price' => '500 บาท'],
        ['volume' => '50 กก.', 'price' => '490 บาท'],
        ['volume' => '100 กก.', 'price' => '470 บาท']
    ];
}

if (empty($pricingFeatures) && CURRENT_LANG === 'th') {
    $pricingFeatures = [
        ['icon' => '📦', 'text' => 'บรรจุถุงซีลขนาด 1 กก. แน่นหนาพร้อมเดินทางไกล'],
        ['icon' => '🌿', 'text' => 'ผลิตสดใหม่ทุกออเดอร์ เพื่อคงคุณภาพและกลิ่นหอม'],
        ['icon' => '🎨', 'text' => 'คละสูตร คละกลิ่นได้ทุกแบบ']
    ];
}
?>

<div class="wrap center">
    <h2 class="h2"><?= htmlspecialchars($p_title) ?></h2>
    <p class="p"><?= htmlspecialchars($p_subtitle) ?></p>

    <table class="price-table">
        <thead>
            <tr>
                <th>ปริมาณ (กิโลกรัม)</th>
                <th>ราคาต่อกก.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pricingTiers as $tier): ?>
                <tr>
                    <td><?= htmlspecialchars($tier['volume'] ?? '') ?></td>
                    <td><?= htmlspecialchars($tier['price'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($pricingFeatures)): ?>
    <ul class="pricing-features" style="list-style: none; padding: 0;">
        <?php foreach ($pricingFeatures as $feature): ?>
            <li>
                <?= htmlspecialchars($feature['icon'] ?? '') ?> 
                <?= htmlspecialchars($feature['text'] ?? '') ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
