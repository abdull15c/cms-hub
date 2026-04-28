<?php
declare(strict_types=1);

// Export the project source into a single text file for code review or analysis.
// By default, real `.env` is excluded to avoid leaking secrets.
// Optional flags:
//   --with-env                 Include `.env`
//   --output=custom-name.txt   Change the output filename

$defaultOutputFilename = 'FULL_PROJECT_CODE.txt';
$outputFilename = $defaultOutputFilename;
$includeEnv = false;

foreach ($argv ?? [] as $arg) {
    if ($arg === '--with-env') {
        $includeEnv = true;
        continue;
    }

    if (strpos($arg, '--output=') === 0) {
        $value = trim(substr($arg, 9));
        if ($value !== '') {
            $outputFilename = basename($value);
        }
    }
}

$rootPath = realpath(__DIR__);
if ($rootPath === false) {
    fwrite(STDERR, "ERROR: Unable to resolve project root.\n");
    exit(1);
}

$outputPath = $rootPath . DIRECTORY_SEPARATOR . $outputFilename;
$ignoreNames = [
    '.git',
    '.idea',
    'vendor',
    'node_modules',
    'storage',
    'images',
    'uploads',
    basename(__FILE__),
    basename($outputPath),
    'source_code.zip',
];

$allowedExtensions = [
    'php',
    'html',
    'css',
    'js',
    'sql',
    'json',
    'txt',
    'md',
    'ps1',
    'sh',
    'yml',
    'yaml',
    'xml',
    'service',
    'timer',
    'conf',
];

$allowedBasenames = [
    '.htaccess',
    '.env.example',
    '.gitignore',
];

if ($includeEnv) {
    $allowedBasenames[] = '.env';
}

$shouldSkipPath = static function (string $relativePath) use ($ignoreNames): bool {
    $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $relativePath)), 'strlen'));
    foreach ($segments as $segment) {
        if (in_array($segment, $ignoreNames, true)) {
            return true;
        }
    }
    return false;
};

$isAllowedFile = static function (string $filename) use ($allowedExtensions, $allowedBasenames): bool {
    if (in_array($filename, $allowedBasenames, true)) {
        return true;
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $extension !== '' && in_array($extension, $allowedExtensions, true);
};

echo "=========================================\n";
echo "   EXPORTING PROJECT TO TXT...\n";
echo "=========================================\n";
echo "Root: {$rootPath}\n";
echo "Output: {$outputPath}\n";
echo "Include .env: " . ($includeEnv ? 'yes' : 'no') . "\n\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS)
);

$files = [];
foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $absolutePath = $fileInfo->getRealPath();
    if ($absolutePath === false) {
        continue;
    }

    $relativePath = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($rootPath))), '/');
    $filename = $fileInfo->getFilename();

    if ($shouldSkipPath($relativePath)) {
        continue;
    }

    if (!$isAllowedFile($filename)) {
        continue;
    }

    $files[$relativePath] = $absolutePath;
}

if ($files === []) {
    fwrite(STDERR, "ERROR: No files matched the export rules.\n");
    exit(1);
}

ksort($files, SORT_NATURAL | SORT_FLAG_CASE);

$fileHandle = fopen($outputPath, 'wb');
if ($fileHandle === false) {
    fwrite(STDERR, "ERROR: Unable to create {$outputPath}. Check write permissions.\n");
    exit(1);
}

fwrite($fileHandle, "PROJECT EXPORT DATE: " . date('Y-m-d H:i:s') . PHP_EOL);
fwrite($fileHandle, "ROOT PATH: " . $rootPath . PHP_EOL);
fwrite($fileHandle, "INCLUDE .ENV: " . ($includeEnv ? 'yes' : 'no') . PHP_EOL);
fwrite($fileHandle, str_repeat('=', 80) . PHP_EOL . PHP_EOL);

$count = 0;
$skippedUnreadable = 0;

foreach ($files as $relativePath => $absolutePath) {
    echo "Processing: {$relativePath}\n";
    $content = file_get_contents($absolutePath);

    if ($content === false) {
        echo "Skipped unreadable file: {$relativePath}\n";
        $skippedUnreadable++;
        continue;
    }

    fwrite($fileHandle, str_repeat('=', 80) . PHP_EOL);
    fwrite($fileHandle, "FILE: {$relativePath}" . PHP_EOL);
    fwrite($fileHandle, str_repeat('=', 80) . PHP_EOL);
    fwrite($fileHandle, $content . PHP_EOL . PHP_EOL . PHP_EOL);
    $count++;
}

fclose($fileHandle);

echo "\n-----------------------------------------\n";
echo "DONE! Exported files: {$count}\n";
echo "Unreadable skipped: {$skippedUnreadable}\n";
echo "Saved to: {$outputPath}\n";
echo "-----------------------------------------\n";
