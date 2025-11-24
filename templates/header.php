<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Dynamic SEO -->
<title><?= htmlspecialchars($SEO['title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($SEO['description']) ?>">
<meta name="keywords" content="<?= htmlspecialchars($SEO['keywords']) ?>">

<!-- Social Sharing -->
<meta property="og:title" content="<?= htmlspecialchars($SEO['title']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($SEO['description']) ?>">
<meta property="og:image" content="<?= $SEO['image'] ?>">
<meta property="og:url" content="<?= URL_PATH . '/' . $lang . '/' . ($route ?? '') ?>">
<meta name="twitter:card" content="summary_large_image">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Main Site CSS -->
<link rel="stylesheet" href="<?= URL_PATH ?>/assets/css/style.css">

</head>
<body>

<header class="navbar">
    <nav>
        <a href="<?= URL_PATH ?>/<?= $lang ?>/"><?= $LANG['home'] ?></a>
        <a href="<?= URL_PATH ?>/<?= $lang ?>/products"><?= $LANG['products'] ?></a>
        <a href="<?= URL_PATH ?>/<?= $lang ?>/about">About</a>
        <a href="<?= URL_PATH ?>/<?= $lang ?>/contact"><?= $LANG['contact'] ?></a>
    </nav>
    <div class="lang-switch">
        <a href="<?= URL_PATH ?>/th/<?= $route ?? '' ?>">TH</a> |
        <a href="<?= URL_PATH ?>/en/<?= $route ?? '' ?>">EN</a>
    </div>
</header>

<main>
