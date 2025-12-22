<?php
/**
 * Language Switcher Component
 * 10-language support dropdown with flag icons
 */

// Available languages with ISO country codes for flags
$languages = [
    'th' => ['name' => 'ไทย', 'country' => 'th'],
    'en' => ['name' => 'English', 'country' => 'gb'],
    'zh' => ['name' => '中文', 'country' => 'cn'],
    'de' => ['name' => 'Deutsch', 'country' => 'de'],
    'fr' => ['name' => 'Français', 'country' => 'fr'],
    'ja' => ['name' => '日本語', 'country' => 'jp'],
    'ko' => ['name' => '한국어', 'country' => 'kr'],
    'ru' => ['name' => 'Русский', 'country' => 'ru'],
    'ar' => ['name' => 'العربية', 'country' => 'sa'],
    'he' => ['name' => 'עברית', 'country' => 'il']
];

$current_lang = $_SESSION['lang'] ?? 'th';
?>

<div class="lang-switcher" id="lang-switcher">
    <button class="lang-current" id="lang-current" onclick="App.toggleLanguageSwitcher()">
        <span class="fi fi-<?= $languages[$current_lang]['country'] ?>"></span>
        <span><?= strtoupper($current_lang) ?></span>
        <i data-lucide="chevron-down" width="16" height="16"></i>
    </button>
    
    <div class="lang-dropdown" id="lang-dropdown">
        <?php foreach ($languages as $code => $lang): ?>
            <div 
                class="lang-option <?= $code === $current_lang ? 'active' : '' ?>" 
                onclick="App.changeLanguage('<?= $code ?>')"
                data-lang="<?= $code ?>"
            >
                <span class="fi fi-<?= $lang['country'] ?>"></span>
                <span><?= $lang['name'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
