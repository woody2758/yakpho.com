<?php
$transcript_file = 'C:/Users/Windows 10/.gemini/antigravity-ide/brain/bca73452-7a45-4378-bd81-8c25b863aee2/.system_generated/logs/transcript.jsonl';
$lines = file($transcript_file);

foreach($lines as $i => $line) {
    if ($i > 100) break;
    $data = json_decode($line, true);
    if(isset($data['type']) && $data['type'] == 'TOOL_RESPONSE') {
        echo json_encode($data) . "\n";
        break;
    }
}
?>
