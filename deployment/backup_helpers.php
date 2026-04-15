<?php
declare(strict_types=1);

use Src\Core\Env;

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

spl_autoload_register(function ($class) {
    $prefixes = [
        'Src\\' => ROOT_PATH . '/src/',
        'Config\\' => ROOT_PATH . '/config/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/src/Core/Env.php';
Env::load();

function market_env(string $key, string $default = ''): string
{
    return (string) Env::get($key, $default);
}

function market_abs_path(string $path): string
{
    $normalized = str_replace('\\', '/', trim($path));
    if ($normalized === '') {
        return ROOT_PATH;
    }
    if (preg_match('~^(?:[A-Za-z]:/|/)~', $normalized)) {
        return rtrim($normalized, '/');
    }
    return ROOT_PATH . '/' . ltrim($normalized, '/');
}

function market_ensure_dir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create directory: ' . $dir);
    }
}

function market_copy_tree(string $source, string $destination): void
{
    if (!file_exists($source)) {
        return;
    }

    if (is_file($source)) {
        market_ensure_dir(dirname($destination));
        if (!copy($source, $destination)) {
            throw new RuntimeException('Unable to copy file: ' . $source);
        }
        return;
    }

    market_ensure_dir($destination);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $target = $destination . '/' . str_replace('\\', '/', $iterator->getSubPathName());
        if ($item->isDir()) {
            market_ensure_dir($target);
            continue;
        }

        market_ensure_dir(dirname($target));
        if (!copy($item->getPathname(), $target)) {
            throw new RuntimeException('Unable to copy file: ' . $item->getPathname());
        }
    }
}

function market_delete_tree(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path)) {
        if (!unlink($path)) {
            throw new RuntimeException('Unable to delete file: ' . $path);
        }
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $target = $item->getPathname();
        if ($item->isDir()) {
            if (!rmdir($target)) {
                throw new RuntimeException('Unable to delete directory: ' . $target);
            }
            continue;
        }

        if (!unlink($target)) {
            throw new RuntimeException('Unable to delete file: ' . $target);
        }
    }

    if (!rmdir($path)) {
        throw new RuntimeException('Unable to delete directory: ' . $path);
    }
}

function market_parse_args(array $argv): array
{
    $args = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (strncmp($arg, '--', 2) !== 0) {
            continue;
        }

        $arg = substr($arg, 2);
        if (strpos($arg, '=') !== false) {
            [$key, $value] = explode('=', $arg, 2);
            $args[$key] = $value;
            continue;
        }

        $args[$arg] = true;
    }

    return $args;
}

function market_run_process(array $command, array $extraEnv = [], ?string $stdinFile = null, ?string $stdoutFile = null): string
{
    $descriptors = [
        0 => $stdinFile !== null ? ['file', $stdinFile, 'r'] : ['pipe', 'r'],
        1 => $stdoutFile !== null ? ['file', $stdoutFile, 'w'] : ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $env = $_ENV;
    foreach ($extraEnv as $key => $value) {
        $env[$key] = $value;
    }

    $process = proc_open($command, $descriptors, $pipes, ROOT_PATH, $env);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start process: ' . $command[0]);
    }

    if ($stdinFile === null && isset($pipes[0]) && is_resource($pipes[0])) {
        fclose($pipes[0]);
    }

    $stdout = '';
    if ($stdoutFile === null && isset($pipes[1]) && is_resource($pipes[1])) {
        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);
    }

    $stderr = '';
    if (isset($pipes[2]) && is_resource($pipes[2])) {
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);
    }

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        $message = trim($stderr) !== '' ? trim($stderr) : ('Command failed with exit code ' . $exitCode);
        throw new RuntimeException($message);
    }

    return trim($stdout);
}

function market_parse_env_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $data = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'((?:[^'\\\\]|\\\\.)*)'$/", $value, $matches)) {
            $value = $matches[1];
        } else {
            $value = trim(explode(' #', $value, 2)[0]);
        }

        $data[$name] = $value;
    }

    return $data;
}
