<?php
// Function to fetch data from Esther REST API
if (!function_exists('fetch_esther_api')) {
    function fetch_esther_api($endpoint, $params = []) {
        if (!defined('ESTHER_API_URL')) {
            return null;
        }
        
        $url = ESTHER_API_URL . '/' . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Use cURL for better performance and error handling
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
        // In local dev, disable SSL verification if using https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status']) {
                return $data;
            }
        }
        
        return null;
    }
}

// Global Language Detection
// 1. Get from URL (?lang=xx)
// 2. Get from Session
// 3. Get from Cookie
// 4. Default to 'th'

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_lang = 'th';

if (isset($_GET['lang'])) {
    $current_lang = strtolower($_GET['lang']);
    $_SESSION['yakpho_lang'] = $current_lang;
    setcookie('yakpho_lang', $current_lang, time() + (86400 * 30), "/");
} elseif (isset($_SESSION['yakpho_lang'])) {
    $current_lang = $_SESSION['yakpho_lang'];
} elseif (isset($_COOKIE['yakpho_lang'])) {
    $current_lang = $_COOKIE['yakpho_lang'];
}

if (!defined('CURRENT_LANG')) {
    define('CURRENT_LANG', $current_lang);
}
?>
