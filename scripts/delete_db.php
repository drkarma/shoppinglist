<?php
declare(strict_types=1);
$dbFile = __DIR__ . '/../data/shoppinglist.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
    echo "Database deleted at {$dbFile}" . PHP_EOL;
} else {
    echo "No database found." . PHP_EOL;
}