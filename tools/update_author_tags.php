<?php
// tools/update_author_tags.php

$directory = __DIR__ . '/../';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

$count = 0;
$patterns = [
    '/@Author:\s*(AI|Ai assistant|Ai|Assistant|AI Assistant)/i'
];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = $content;
        
        foreach ($patterns as $pattern) {
            $newContent = preg_replace($pattern, '@Author: Nefi', $newContent);
        }
        
        if ($content !== $newContent) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}

echo "Total files updated: $count\n";
?>
