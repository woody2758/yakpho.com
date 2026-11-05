<?php
$transcript_file = 'C:/Users/Windows 10/.gemini/antigravity-ide/brain/bca73452-7a45-4378-bd81-8c25b863aee2/.system_generated/logs/transcript.jsonl';
$lines = file($transcript_file);

$fb_groups_lines = [];
$max_line = 0;

foreach($lines as $line) {
    $data = json_decode($line, true);
    if(isset($data['tool_calls'])) {
        // check if this step has replace_file_content or multi_replace_file_content
        // actually easier to look at view_file outputs.
    }
    if($data['type'] == 'PLANNER_RESPONSE' && isset($data['content'])) {
        // we might not have the full file here.
    }
    if($data['type'] == 'TOOL_RESPONSE' && isset($data['content']) && strpos($data['content'], 'fb_groups.php') !== false) {
        // parse the view_file output.
        // The output format is: <line_number>: <original_line>
        preg_match_all('/^(\d+):\s(.*)$/m', $data['content'], $matches, PREG_SET_ORDER);
        foreach($matches as $m) {
            $line_num = (int)$m[1];
            $content = $m[2];
            $fb_groups_lines[$line_num] = $content;
            if($line_num > $max_line) $max_line = $line_num;
        }
    }
}

// also process replace file contents to apply patches
// wait, if I apply patches, it gets complicated.

echo "Max line: " . $max_line . "\n";
echo "Total lines captured: " . count($fb_groups_lines) . "\n";

// output to file
ksort($fb_groups_lines);
file_put_contents('recovered_fb_groups.php', implode("\n", $fb_groups_lines));
?>
