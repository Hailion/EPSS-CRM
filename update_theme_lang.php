<?php
// Function to recursively get all PHP files
function getPhpFiles($dir) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, getPhpFiles($path));
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    
    return $files;
}

// Function to update a PHP file
function updatePhpFile($file) {
    $content = file_get_contents($file);
    
    // Skip if already has theme/language support
    if (strpos($content, 'getLanguageHtmlAttributes()') !== false) {
        return false;
    }
    
    // Add language attributes to HTML tag
    $content = preg_replace(
        '/<html\s+/',
        '<html ' . getLanguageHtmlAttributes() . ' ',
        $content,
        1
    );
    
    // Add theme class to body tag
    $content = preg_replace(
        '/<body\s+/',
        '<body class="' . ($_SESSION['theme'] === 'dark' ? 'dark-theme' : '') . '" ',
        $content,
        1
    );
    
    // Add theme stylesheet if dark theme
    if ($_SESSION['theme'] === 'dark') {
        $content = preg_replace(
            '/<link rel="stylesheet" href="\.\.\/assets\/css\/style\.css">/',
            '<link rel="stylesheet" href="../assets/css/style.css">' . "\n    <link rel=\"stylesheet\" href=\"../assets/css/dark-theme.css\">",
            $content,
            1
        );
    }
    
    // Save updated content
    file_put_contents($file, $content);
    return true;
}

// Get all PHP files
$files = getPhpFiles(__DIR__);

// Update each file
$updated = 0;
foreach ($files as $file) {
    if (updatePhpFile($file)) {
        $updated++;
        echo "Updated: " . $file . "\n";
    }
}

echo "\nTotal files updated: " . $updated . "\n"; 