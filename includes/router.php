<?php
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// map
$routes = [
    ''          => 'home.php',
    'products'  => 'products.php',
    'product'   => 'product-detail.php',
    'about'     => 'home.php',   // เปลี่ยนทีหลังได้
    'contact'   => 'home.php',
];

// break route into segments
$segments = explode('/', $route);
$base = $segments[0] ?? '';
$page_id = $segments[1] ?? null;

// include header
include __DIR__ . '/../templates/header.php';

// call page
if (array_key_exists($base, $routes)) {

    $file = __DIR__ . '/../templates/' . $routes[$base];

    // ถ้ามี product/123
    if ($base === 'product' && $page_id !== null) {
        $product_id = $page_id;    // ส่งตัวแปรให้ product-detail.php
    }

    if (file_exists($file)) {
        include $file;
    } else {
        include __DIR__ . '/../templates/404.php';
    }

} else {
    include __DIR__ . '/../templates/404.php';
}

// include footer
include __DIR__ . '/../templates/footer.php';
