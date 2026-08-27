<?php

declare(strict_types=1);

$viewsDir = __DIR__ . '/../resources/views';

$patterns = [
    '/^<meta charset="UTF-8">\s*$/i',
    '/^<meta name="viewport" content="width=device-width, initial-scale=1\.0">\s*$/i',
    '/^<title>.*<\/title>\s*$/i',
    '/^<script src="https:\/\/cdn\.tailwindcss\.com"><\/script>\s*$/i',
    '/^<script src="https:\/\/unpkg\.com\/lucide@latest\/dist\/umd\/lucide\.js"><\/script>\s*$/i',
    '/^<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/chart\.js"><\/script>\s*$/i',
    '/^<script src="https:\/\/unpkg\.com\/leaflet@.*\/dist\/leaflet\.js"><\/script>\s*$/i',
    '/^<link rel="stylesheet" href="https:\/\/unpkg\.com\/leaflet@.*\/dist\/leaflet\.css".*\/>\s*$/i',
    '/^<link rel="stylesheet" href="\.\.\/assets\/css\/app\.css">\s*$/i',
    '/^<link rel="stylesheet" href="assets\/css\/app\.css">\s*$/i',
    '/^<link rel="stylesheet" href="\.\.\/\.\.\/assets\/css\/app\.css">\s*$/i',
    '/^<script type="module" src="\.\.\/assets\/js\/theme\.js"><\/script>\s*$/i',
    '/^<script type="module" src="\.\.\/\.\.\/assets\/js\/theme\.js"><\/script>\s*$/i',
    '/^<script type="module" src="assets\/js\/theme\.js"><\/script>\s*$/i',
];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

foreach ($rii as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (str_ends_with($path, 'shared' . DIRECTORY_SEPARATOR . 'head.php')) {
        continue;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }

    $original = $lines;
    $cleaned = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Remove blank lines that only contain whitespace
        if ($trimmed === '' && end($cleaned) === '') {
            continue;
        }

        $remove = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $trimmed)) {
                $remove = true;
                break;
            }
        }

        if (!$remove) {
            $cleaned[] = $line;
        }
    }

    // Trim leading blank lines
    while ($cleaned !== [] && trim($cleaned[0]) === '') {
        array_shift($cleaned);
    }

    if ($cleaned !== $original) {
        file_put_contents($path, implode("\n", $cleaned) . "\n");
        echo "Cleaned: $path\n";
    }
}

echo "Done.\n";
