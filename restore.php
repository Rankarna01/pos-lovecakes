<?php
$logPath = '/Users/calvin/.gemini/antigravity-ide/brain/1b223b06-9ab7-4613-96ed-93f882328c9b/.system_generated/logs/transcript_full.jsonl';
$file = fopen($logPath, 'r');
if (!$file) die("Could not open log");

$tambahPhpStr = '';
$tambahJsStr = '';

while (($line = fgets($file)) !== false) {
    if (strpos($line, '"Creating pos/opname/tambah.php"') !== false) {
        $data = json_decode($line, true);
        foreach ($data['tool_calls'] as $call) {
            if ($call['name'] === 'write_to_file' && strpos($call['args']['TargetFile'], 'tambah.php') !== false) {
                $content = $call['args']['CodeContent'];
                // Removing outer quotes and unescaping \n, \", etc.
                // The CodeContent in args is stored as a JSON string inside the JSON object, so it's a string literal.
                // Actually, json_decode already handles it if the outer JSON is valid!
                // Wait, if it's stored as a string, it's just a string! Let's check if it needs double decode.
                $content = json_decode($content, true);
                if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
                   // If not double encoded, just use it
                   $content = $call['args']['CodeContent'];
                }
                file_put_contents('/Applications/XAMPP/xamppfiles/htdocs/pos-lovecakes/pos/opname/tambah.php', $content);
            }
        }
    }
    
    if (strpos($line, '"Creating pos/opname/tambah.js"') !== false) {
        $data = json_decode($line, true);
        foreach ($data['tool_calls'] as $call) {
            if ($call['name'] === 'write_to_file' && strpos($call['args']['TargetFile'], 'tambah.js') !== false) {
                $content = $call['args']['CodeContent'];
                $content = json_decode($content, true);
                if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
                   $content = $call['args']['CodeContent'];
                }
                file_put_contents('/Applications/XAMPP/xamppfiles/htdocs/pos-lovecakes/pos/opname/tambah.js', $content);
            }
        }
    }
}
fclose($file);
echo "Restored tambah.php and tambah.js\n";
