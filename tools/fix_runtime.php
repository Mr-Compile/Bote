<?php

declare(strict_types=1);

$baseDir = realpath(__DIR__ . '/..');
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir . '/app/Http/Controllers'));

foreach ($iter as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = (string) file_get_contents($path);
    $original = $content;

    // 1. Replace check_db_health($conn) calls
    $content = preg_replace('/\bcheck_db_health\(\$conn\)/', '$this->checkDbHealth()', $content);

    // 2. Replace BoteBankUploadHandler method call chain: $uploader->handleImageUpload on unknown class is fine at runtime
    //    but ensure instantiation has leading backslash (already done by migrate.php)

    // 3. Ensure DateTime is fully qualified inside namespaced controllers
    $content = preg_replace('/\bnew DateTime\b/', 'new \\DateTime', $content);
    $content = preg_replace('/\bDateTime::/', '\\DateTime::', $content);

    // 4. Ensure Exception is fully qualified (migrate.php should have done this; catch-up for any misses)
    $content = preg_replace('/\bcatch \(Exception\b/', 'catch (\\Exception', $content);
    $content = preg_replace('/\bthrow new Exception\b/', 'throw new \\Exception', $content);

    // 5. Add $conn = $this->container->database->connection(); at the top of render() if $conn is used
    if (str_contains($content, '$conn') && !str_contains($content, '$conn = $this->container->database->connection()')) {
        $content = preg_replace(
            '/(public function render\(\): void\s*\{)(\s*)/',
            '$1$2        $conn = $this->container->database->connection();$2',
            $content,
        );
    }

    // 6. Replace any remaining bare $conn-> use with $this->legacy()-> (should already be done, but catch any inside inner functions)
    $content = preg_replace('/\$conn->/', '$this->legacy()->', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Patched {$path}\n";
    }
}

echo "Runtime patch pass complete.\n";
