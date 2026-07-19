<?php
$dir = '/Applications/XAMPP/xamppfiles/htdocs/pos-lovecakes/pos/';
$target = "COALESCE(p.name, sd.custom_name)";
$replacement = "COALESCE(p.name, sd.custom_name, 'Produk Dihapus')";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, $target) !== false) {
            // But ensure we don't replace if it's already 'Produk Dihapus'
            if (strpos($content, $replacement) === false) {
                $newContent = str_replace($target, $replacement, $content);
                file_put_contents($file->getPathname(), $newContent);
                $count++;
            }
        }
    }
}
echo json_encode(['status' => 'success', 'files_updated' => $count]);
