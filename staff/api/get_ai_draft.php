<?php
require_once "../db.php";
require_once "get_fb_settings.php"; // We need the API key from here potentially, or just fetch directly

header('Content-Type: application/json');

// API Key Validation (Staff login usually handles this, but for AJAX we check the secret)
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID Required']);
    exit;
}

try {
    // 1. Get Group Info
    $stmt = $db->prepare("SELECT * FROM facebook_groups WHERE id = ?");
    $stmt->execute([$id]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$g) {
        echo json_encode(['ok' => false, 'msg' => 'Group Not Found']);
        exit;
    }

    // 2. Get Gemini Key
    $configs = $db->query("SELECT cfg_key, cfg_value FROM fb_configs")->fetchAll(PDO::FETCH_KEY_PAIR);
    $gemini_key = $configs['gemini_api_key'] ?? '';

    if (!$gemini_key) {
        echo json_encode(['ok' => false, 'msg' => 'Gemini API Key not set in settings']);
        exit;
    }

    // 3. Define AI Posting Strategy Prompt
    $marker = $g['strategy_marker'] ?? 'Observe';
    
    // Case White: Blacklist
    if ($marker === 'Blacklist') {
        echo json_encode(['ok' => false, 'msg' => 'Case ⚪: Blacklisted. Skipping this group.']);
        exit;
    }

    // Prepare Strategy Instruction for Gemini
    $strategy_instruction = "";
    if ($marker === 'Observe') {
        echo json_encode(['ok' => false, 'msg' => 'Case 🔴: Observer Only. No content generated. Send Human Admin to review manually.']);
        exit;
    } elseif ($marker === 'Soft') {
        $strategy_instruction = "Case 🟡: Value First (Soft Sell Only). 
        Objective: Establish trust and offer solutions, not products.
        Content Rules: Focus on solving 'Pain Points' (therapist hand fatigue, customer relaxation, shipping safety).
        PROHIBITED: Price lists, discount percentages, or 'Buy Now' links.
        CTA: Invite meaningful discussion or offer a private consultation via DM.
        Tone: Helpful, Expert, Community-oriented.";
    } elseif ($marker === 'Hard') {
        $strategy_instruction = "Case 🟢: Direct Post (Hard Sell Allowed).
        Objective: Drive direct sales and conversions.
        Content Rules: Clearly state product benefits (3 Formulas / 12 Scents). Include transparent pricing (Retail 690 THB / Wholesale tiers). Highlight bulk value (Buy 10 Get 2, etc.).
        CTA: Direct link to [yakpho.com] or 'Comment below to order'.
        Tone: Energetic, Result-oriented, Persuasive.";
    }

    $prompt = "Role: You are the Social Media Engagement Specialist for 'Yakpho Aroma.'
    Instructions: Follow this strategy: " . $strategy_instruction . "
    
    Target Group: " . $g['group_name'] . " (" . $g['category_tag'] . ")
    
    Write a powerful, naturalIntroduction Post for this group in THAI language. 
    Output ONLY THE POST CONTENT without extra words.";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $gemini_key;
    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $res_json = json_decode($response, true);
    curl_close($ch);

    $draft = $res_json['candidates'][0]['content']['parts'][0]['text'] ?? 'Error generating draft';

    echo json_encode(['ok' => true, 'draft' => $draft]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
