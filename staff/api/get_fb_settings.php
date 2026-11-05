<?php
require_once "../db.php";

header('Content-Type: application/json');

// API Key Validation
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    // 0. Auto-migrate: Create history table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS `fb_scrape_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `keyword` varchar(255) NOT NULL,
        `scraped_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `keyword` (`keyword`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 1. Get Keywords (Robust: No is_active needed)
    $base_keywords = $db->query("SELECT keyword FROM fb_keywords")->fetchAll(PDO::FETCH_COLUMN);
    
    // 1.5 Get Countries and Combine
    $countries = [];
    try {
        $countries = $db->query("SELECT name_th FROM countries")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) { $countries = []; }

    // 1.6 Get Expat Cities and Combine
    $cities_file = __DIR__ . '/../bot/thai_expat_cities.txt';
    $expat_cities = [];
    if (file_exists($cities_file)) {
        $lines = file($cities_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $expat_cities[] = trim($line);
        }
    }

    $keywords = $base_keywords;
    
    // 1.8 Get Scraping History
    $history = [];
    try {
        $history = $db->query("SELECT keyword FROM fb_scrape_history")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
    
    // 2. Get Configs
    $configs = $db->query("SELECT cfg_key, cfg_value FROM fb_configs")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // 3. Get Cities
    $cities = [];
    if (($configs['use_cities_mode'] ?? '0') == '1') {
        try {
            $cities = $db->query("SELECT name FROM cities ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) { $cities = []; }
    }

    // 4. Get Existing URLs to prevent duplicate AI processing
    $existing_urls = [];
    try {
        $existing_urls = $db->query("SELECT group_url FROM facebook_groups")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) { $existing_urls = []; }

    echo json_encode([
        'status' => 'success',
        'settings' => [
            'enable_gemini' => ($configs['enable_gemini'] ?? '1') == '1',
            'gemini_api_key' => $configs['gemini_api_key'] ?? '',
            'keywords' => $keywords,
            'countries' => $countries,
            'expat_cities' => $expat_cities,
            'history' => $history,
            'scroll_depth' => (int)($configs['scroll_depth'] ?? 3),
            'public_only' => ($configs['public_only'] ?? '1') == '1',
            'use_cities_mode' => ($configs['use_cities_mode'] ?? '0') == '1',
            'cities' => $cities,
            'cookies' => [
                'worldwide' => $configs['cookie_json_worldwide'] ?? '',
                'domestic'  => $configs['cookie_json_domestic'] ?? '',
                'esther'    => $configs['cookie_json_esther'] ?? ''
            ],
            'active_persona' => $configs['active_persona'] ?? 'worldwide',
            'existing_urls' => $existing_urls
        ]
    ]);

} catch (PDOException $e) {
    // Return explicit error status if DB fails
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
