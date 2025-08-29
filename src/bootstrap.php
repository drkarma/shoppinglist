<?php
declare(strict_types=1);
// Ladda app-konfig
$GLOBALS['app_config'] = require __DIR__ . '/config.php';
function config(string $path, $default = null) {
    $cfg = $GLOBALS['app_config'] ?? [];
    foreach (explode('.', $path) as $k) {
        if (!is_array($cfg) || !array_key_exists($k, $cfg)) return $default;
        $cfg = $cfg[$k];
    }
    return $cfg;
}


spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

function view(string $name): string {
    return __DIR__ . '/views/' . $name . '.php';
}
