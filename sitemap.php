<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma & Esther Aroma — Dynamic XML Sitemap Generator
 * ============================================================
 * Always outputs the latest, real-time products, categories, 
 * and blog articles directly from the database for Googlebot,
 * Bingbot, and AI Search Crawlers.
 */

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/includes/config.php';

$domain = 'https://yakpho.com';
$languages = ['th', 'en', 'zh', 'ja', 'ko', 'de', 'fr', 'ru', 'ar', 'he'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">

  <!-- Core Static Pages -->
  <?php
  $corePages = [
      ['path' => '',                'priority' => '1.0', 'changefreq' => 'daily'],
      ['path' => 'products',        'priority' => '0.9', 'changefreq' => 'daily'],
      ['path' => 'corporate-gifts', 'priority' => '0.8', 'changefreq' => 'weekly'],
      ['path' => 'gift-sets',       'priority' => '0.8', 'changefreq' => 'weekly'],
      ['path' => 'about',           'priority' => '0.8', 'changefreq' => 'monthly'],
      ['path' => 'brandstory',      'priority' => '0.8', 'changefreq' => 'monthly'],
      ['path' => 'gallery',         'priority' => '0.8', 'changefreq' => 'weekly'],
      ['path' => 'faqs',            'priority' => '0.8', 'changefreq' => 'weekly'],
      ['path' => 'blog',            'priority' => '0.9', 'changefreq' => 'daily'],
      ['path' => 'stores',          'priority' => '0.7', 'changefreq' => 'monthly'],
      ['path' => 'contact',         'priority' => '0.7', 'changefreq' => 'monthly'],
  ];

  foreach ($corePages as $cp):
      $locUrl = $domain . ($cp['path'] !== '' ? '/' . $cp['path'] : '');
  ?>
  <url>
    <loc><?= htmlspecialchars($locUrl) ?></loc>
    <?php foreach ($languages as $lg): ?>
    <xhtml:link rel="alternate" hreflang="<?= $lg ?>" href="<?= htmlspecialchars($domain . '/' . $lg . ($cp['path'] !== '' ? '/' . $cp['path'] : '')) ?>" />
    <?php endforeach; ?>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($locUrl) ?>" />
    <changefreq><?= $cp['changefreq'] ?></changefreq>
    <priority><?= $cp['priority'] ?></priority>
  </url>
  <?php endforeach; ?>

  <!-- Dynamic Product Categories -->
  <?php
  try {
      $catStmt = $db->query("SELECT productcat_id FROM productcat WHERE productcat_del = 0 ORDER BY productcat_id ASC");
      while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)):
          $catUrl = $domain . '/th/products?cat_id=' . (int)$cat['productcat_id'];
  ?>
  <url>
    <loc><?= htmlspecialchars($catUrl) ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php
      endwhile;
  } catch (Exception $e) {}
  ?>

  <!-- Dynamic Live Products -->
  <?php
  try {
      $prodStmt = $db->query("
          SELECT product_id, 
                 COALESCE(product_updated, product_created, NOW()) as lastmod 
          FROM product 
          WHERE product_del = 0 
          ORDER BY product_id DESC
      ");
      while ($prod = $prodStmt->fetch(PDO::FETCH_ASSOC)):
          $pId = (int)$prod['product_id'];
          $pLoc = $domain . '/th/product/' . $pId;
          $modDate = date('Y-m-d', strtotime((string)$prod['lastmod']));
  ?>
  <url>
    <loc><?= htmlspecialchars($pLoc) ?></loc>
    <?php foreach ($languages as $lg): ?>
    <xhtml:link rel="alternate" hreflang="<?= $lg ?>" href="<?= htmlspecialchars($domain . '/' . $lg . '/product/' . $pId) ?>" />
    <?php endforeach; ?>
    <lastmod><?= $modDate ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php
      endwhile;
  } catch (Exception $e) {}
  ?>

  <!-- Dynamic Blog & Herbal Spa Articles -->
  <?php
  try {
      $blogStmt = $db->query("
          SELECT blog_id, 
                 COALESCE(blog_date, NOW()) as lastmod 
          FROM blog 
          WHERE blog_del = 0 
          ORDER BY blog_id DESC
      ");
      while ($b = $blogStmt->fetch(PDO::FETCH_ASSOC)):
          $bId = (int)$b['blog_id'];
          $bLoc = $domain . '/th/blog/' . $bId;
          $bMod = date('Y-m-d', strtotime((string)$b['lastmod']));
  ?>
  <url>
    <loc><?= htmlspecialchars($bLoc) ?></loc>
    <?php foreach ($languages as $lg): ?>
    <xhtml:link rel="alternate" hreflang="<?= $lg ?>" href="<?= htmlspecialchars($domain . '/' . $lg . '/blog/' . $bId) ?>" />
    <?php endforeach; ?>
    <lastmod><?= $bMod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php
      endwhile;
  } catch (Exception $e) {}
  ?>

</urlset>

