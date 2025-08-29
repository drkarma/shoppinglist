<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Controller\ListController;

// Återanvänder ensureSchema via klassens db()
$ref = new ReflectionClass(\App\Controller\ListController::class);
$ctrl = $ref->newInstanceWithoutConstructor();
$driver = config('db.driver', 'sqlite');

if ($driver === 'mysql') {
    // öppna mysql-anslutning
    $host = config('db.mysql.host'); $port=(int)config('db.mysql.port',3306);
    $name = config('db.mysql.name'); $user=config('db.mysql.user'); $pass=config('db.mysql.pass');
    $charset = config('db.mysql.charset','utf8mb4');
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
    // skapa schema
    $method = (new ReflectionClass(\App\Controller\ListController::class))->getMethod('ensureSchema');
    $method->setAccessible(true);
    $method->invoke($ctrl, $pdo, 'mysql');
    echo "Database (MySQL) initialized: $name@$host:$port\n";
    exit;
}

// sqlite fallback
$path = config('db.sqlite_path', __DIR__ . '/../data/shoppinglist.sqlite');
$dir = dirname($path); if (!is_dir($dir)) @mkdir($dir, 0777, true);
$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');
$method = (new ReflectionClass(\App\Controller\ListController::class))->getMethod('ensureSchema');
$method->setAccessible(true);
$method->invoke($ctrl, $pdo, 'sqlite');
echo "Database (SQLite) initialized at $path\n";
