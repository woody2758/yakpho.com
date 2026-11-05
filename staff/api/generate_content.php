<?php
require_once "../db.php";
header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$strategy = $_GET['strategy'] ?? 'Green';

try {
    // Get Gemini Key
    $configs = $db->query("SELECT cfg_key, cfg_value FROM fb_configs")->fetchAll(PDO::FETCH_KEY_PAIR);
    $gemini_key = $configs['gemini_api_key'] ?? '';

    if (!$gemini_key) {
        echo json_encode(['ok' => false, 'msg' => 'Gemini API Key not set in settings']);
        exit;
    }

    $strategy_instruction = "";
    if ($strategy === 'Yellow') {
        $strategy_instruction = $configs['ai_prompt_yellow'] ?? "";
    } elseif ($strategy === 'Green') {
        $strategy_instruction = $configs['ai_prompt_green'] ?? "";
    } else {
        $strategy_instruction = $configs['ai_prompt_red'] ?? "";
    }

    $role_instruction = $configs['ai_prompt_role'] ?? "";

    $prompt = $role_instruction . "\n\n" . $strategy_instruction;

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

    if (isset($res_json['error'])) {
        echo json_encode(['ok' => false, 'msg' => 'Gemini API Error: ' . ($res_json['error']['message'] ?? json_encode($res_json['error']))]);
        exit;
    }

    $draft = $res_json['candidates'][0]['content']['parts'][0]['text'] ?? 'Error generating draft';

    echo json_encode(['ok' => true, 'draft' => trim($draft)]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
