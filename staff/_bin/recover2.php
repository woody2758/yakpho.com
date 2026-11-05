<?php
$transcript_file = 'C:/Users/Windows 10/.gemini/antigravity-ide/brain/bca73452-7a45-4378-bd81-8c25b863aee2/.system_generated/logs/transcript.jsonl';
$lines = file($transcript_file);

foreach($lines as $i => $line) {
    if ($i > 150) break; // Check first 150 lines
    $data = json_decode($line, true);
    if(isset($data['tool_calls'])) {
        foreach($data['tool_calls'] as $tc) {
            if ($tc['name'] == 'default_api:view_file' || $tc['name'] == 'view_file') {
                echo "Found view_file in step " . $data['step_index'] . "\n";
            }
        }
    }
    if($data['type'] == 'TOOL_RESPONSE') {
        echo "TOOL_RESPONSE in step " . $data['step_index'] . "\n";
        echo substr(json_encode($data), 0, 200) . "\n";
    }
}
?>
