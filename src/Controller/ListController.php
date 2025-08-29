<?php declare(strict_types=1);

namespace App\Controller;

class ListController
{
    private string $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/../../data/shoppinglist.sqlite';
    }


    private function db(): \PDO
    {
        $driver = config('db.driver', 'sqlite');

        if ($driver === 'mysql') {
            $host    = config('db.mysql.host');
            $port    = (int)config('db.mysql.port', 3306);
            $name    = config('db.mysql.name');
            $user    = config('db.mysql.user');
            $pass    = config('db.mysql.pass');
            $charset = config('db.mysql.charset', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $this->ensureSchema($pdo, 'mysql');
            return $pdo;
        }

        // fallback: sqlite (lokalt)
        $path = config('db.sqlite_path', __DIR__ . '/../../data/shoppinglist.sqlite');
        $dir  = \dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        $pdo = new \PDO('sqlite:' . $path);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $this->ensureSchema($pdo, 'sqlite');
        return $pdo;
    }


private function ensureSchema(\PDO $pdo, string $driver): void
{
    if ($driver === 'mysql') {
        // InnoDB + utf8mb4; DECIMAL för kostnad; TINYINT(1) för checked
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lists (
              id         VARCHAR(20) PRIMARY KEY,
              title      VARCHAR(255) NOT NULL,
              type       VARCHAR(32)  NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS list_items (
              id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              list_id    VARCHAR(20) NOT NULL,
              name       VARCHAR(255) NOT NULL,
              quantity   VARCHAR(100),
              cost       DECIMAL(10,2) NULL,
              checked    TINYINT(1) NOT NULL DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_list_items_list
                FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
              INDEX idx_items_list (list_id),
              INDEX idx_items_list_checked (list_id, checked)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Mjuk migration om äldre DB saknar 'cost'
        try {
            $hasCost = false;
            $cols = $pdo->query("SHOW COLUMNS FROM list_items")->fetchAll();
            foreach ($cols as $c) {
                if (strcasecmp($c['Field'], 'cost') === 0) { $hasCost = true; break; }
            }
            if (!$hasCost) {
                $pdo->exec("ALTER TABLE list_items ADD COLUMN cost DECIMAL(10,2) NULL AFTER quantity");
            }
        } catch (\Throwable $e) { /* ignore */ }

        return;
    }

    // sqlite-varianten (som du hade)
    $pdo->exec("CREATE TABLE IF NOT EXISTS lists (
        id TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        type TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS list_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        list_id TEXT NOT NULL,
        name TEXT NOT NULL,
        quantity TEXT,
        cost REAL,
        checked INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(list_id) REFERENCES lists(id) ON DELETE CASCADE
    )");
    try {
        $cols = $pdo->query('PRAGMA table_info(list_items)')->fetchAll() ?: [];
        $hasCost = false;
        foreach ($cols as $c) if (($c['name'] ?? '') === 'cost') { $hasCost = true; break; }
        if (!$hasCost) { $pdo->exec("ALTER TABLE list_items ADD COLUMN cost REAL"); }
    } catch (\Throwable $e) {}
}





    private function baseUrl(): string
    {
        // Proxy-aware
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $host  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? null;
        $port  = $_SERVER['HTTP_X_FORWARDED_PORT']  ?? null;

        $https = ($proto === 'https')
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($port ?? ($_SERVER['SERVER_PORT'] ?? null)) == 443);

        $scheme = $https ? 'https' : 'http';
        $host   = $host ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');

        // Scriptkatalog (t.ex. /test)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        if ($scriptDir === '/' || $scriptDir === '\\') $scriptDir = '';

        return $scheme . '://' . $host . $scriptDir;
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'home';

        // Partial för auto-refresh
        if ($action === 'view' && (($_GET['partial'] ?? '') === 'rows')) {
            $id = $_GET['id'] ?? '';
            if ($id !== '') { $this->renderRowsPartial($id); }
            return;
        }

        switch ($action) {
            case 'home':
                $this->renderHome(); return;

            case 'create':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') { $this->createList(); }
                else { $this->renderHome(); }
                return;

            case 'view':
                $id = $_GET['id'] ?? '';
                if ($id === '') { $this->renderHome(); return; }
                $this->renderList($id); return;

            case 'add':
            case 'add_item':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $this->addItem();
                return;

            case 'check':
            case 'toggle_item':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $this->toggleItem();
                return;

            case 'delete':
            case 'delete_item':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $this->deleteItem();
                return;

            case 'update': // inline edit (AJAX)
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $this->updateItem();
                return;
            case 'print':
                $id = $_GET['id'] ?? '';
                if (!$id) { header('Location: /?'); exit; }
                $this->renderPrint($id);
                return;

            case 'diagnostics':
                $this->diagnostics(); return;

            default:
                $this->renderHome(); return;
        }
    }

    private function createList(): void
    {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') { header('Location: ?'); return; }
        $id = 'l' . bin2hex(random_bytes(6));
        $pdo = $this->db();
        $stmt = $pdo->prepare('INSERT INTO lists (id, title, type) VALUES (?, ?, ?)');
        $stmt->execute([$id, $title, 'shopping']);
        header('Location: ?action=view&id=' . urlencode($id));
        exit;
    }

    private function renderHome(): void
    {
        $baseUrl = $this->baseUrl();
        include \view('home');
    }

    private function fetchList(string $id): ?array
    {
        $pdo = $this->db();
        $stmt = $pdo->prepare('SELECT * FROM lists WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fetchItems(string $id): array
    {
        $pdo = $this->db();
        $stmt = $pdo->prepare('SELECT * FROM list_items WHERE list_id = ? ORDER BY checked ASC, id ASC');
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function renderList(string $id): void
    {
        $list = $this->fetchList($id);
        if (!$list) { http_response_code(404); echo '<!doctype html><meta charset="utf-8"><div style="padding:2rem;font-family:sans-serif">Lista saknas.</div>'; return; }
        $items = $this->fetchItems($id);
        $baseUrl = $this->baseUrl();
        include \view('view_list');
    }

    private function renderPrint(string $id): void
    {
        $list = $this->fetchList($id);
        if (!$list) {
            http_response_code(404);
            echo "Listan finns inte.";
            return;
        }

        $items = $this->fetchItems($id);

        $remaining = array_values(array_filter($items, fn($r) => (int)($r['checked'] ?? 0) === 0));
        $done      = array_values(array_filter($items, fn($r) => (int)($r['checked'] ?? 0) === 1));

        $totalCount = count($items);
        $doneCount  = count($done);
        $percent    = $totalCount ? (int)round($doneCount / $totalCount * 100) : 0;

        $sumDone = 0.0;
        $sumTotal = 0.0;
        foreach ($items as $it) {
            $p = isset($it['cost']) && is_numeric($it['cost']) ? (float)$it['cost'] : 0.0;
            $sumTotal += $p;
            if ((int)($it['checked'] ?? 0) === 1) $sumDone += $p;
        }

        // Länk till visningsläget
        $baseUrl  = $this->baseUrl();
        $shareUrl = $baseUrl . '/?action=view&id=' . urlencode($id);

        // Gör variablerna synliga för vyn
        /** @var array $list */
        /** @var array $remaining */
        /** @var array $done */
        /** @var int $doneCount */
        /** @var int $totalCount */
        /** @var int $percent */
        /** @var float $sumDone */
        /** @var float $sumTotal */
        /** @var string $shareUrl */

        include view('print_list');
    }	


    private function renderRowsPartial(string $id): void {
        $list = $this->fetchList($id);
        if (!$list) { http_response_code(404); return; }
        $items = $this->fetchItems($id);
        include \view('partials/items_rows');
    }

    private function addItem(): void
    {
        $list_id = $_POST['list_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $costRaw = trim($_POST['cost'] ?? '');
        $cost = null;
        if ($costRaw !== '') {
            $san = preg_replace('/[^0-9,.\-]/', '', $costRaw) ?? '';
            $san = str_replace(',', '.', $san);
            if ($san !== '' && is_numeric($san)) $cost = (float)$san;
        }

        if ($list_id === '' || $name === '') { header('Location: ?'); return; }

        $pdo = $this->db();
        $stmt = $pdo->prepare('INSERT INTO list_items (list_id, name, quantity, cost, checked) VALUES (?, ?, ?, ?, 0)');
        $stmt->execute([$list_id, $name, $quantity, $cost]);
        header('Location: ?action=view&id=' . urlencode($list_id));
        exit;
    }

    private function toggleItem(): void
    {
        $list_id = $_POST['list_id'] ?? '';
        $item_id = $_POST['item_id'] ?? ($_POST['id'] ?? '');
        $checked = isset($_POST['checked']) ? 1 : 0;
        if ($list_id === '' || $item_id === '') { header('Location: ?'); return; }
        $pdo = $this->db();
        $stmt = $pdo->prepare('UPDATE list_items SET checked = ? WHERE id = ? AND list_id = ?');
        $stmt->execute([$checked, $item_id, $list_id]);
        header('Location: ?action=view&id=' . urlencode($list_id));
        exit;
    }

    private function deleteItem(): void
    {
        $list_id = $_POST['list_id'] ?? '';
        $item_id = $_POST['item_id'] ?? ($_POST['id'] ?? '');
        if ($list_id === '' || $item_id === '') { header('Location: ?'); return; }
        $pdo = $this->db();
        $stmt = $pdo->prepare('DELETE FROM list_items WHERE id = ? AND list_id = ?');
        $stmt->execute([$item_id, $list_id]);
        header('Location: ?action=view&id=' . urlencode($list_id));
        exit;
    }

    private function updateItem(): void
    {
        $list_id = $_POST['list_id'] ?? '';
        $item_id = $_POST['item_id'] ?? '';
        $field   = $_POST['field']   ?? '';
        $value   = trim($_POST['value'] ?? '');

        $ok = false; $msg = '';
        try {
            if ($list_id && $item_id && in_array($field, ['name','quantity','cost'], true)) {
                $pdo = $this->db();
                if ($field === 'cost') {
                    $san = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';
                    $san = str_replace(',', '.', $san);
                    $num = ($san === '' ? null : (is_numeric($san) ? (float)$san : null));
                    $stmt = $pdo->prepare('UPDATE list_items SET cost = ? WHERE id = ? AND list_id = ?');
                    $ok = $stmt->execute([$num, $item_id, $list_id]);
                } else {
                    $sql = ($field === 'name')
                        ? 'UPDATE list_items SET name = ? WHERE id = ? AND list_id = ?'
                        : 'UPDATE list_items SET quantity = ? WHERE id = ? AND list_id = ?';
                    $stmt = $pdo->prepare($sql);
                    $ok = $stmt->execute([$value, $item_id, $list_id]);
                }
            } else {
                $msg = 'Bad request';
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => (bool)$ok, 'message' => $msg]);
        exit;
    }

    private function diagnostics(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        $out = [];
        $out[] = '<!doctype html><meta charset="utf-8"><title>Diagnostics</title><style>body{font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;padding:20px}code{background:#f6f8fa;padding:2px 4px;border-radius:4px}table{border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px 8px}h2{margin-top:1.2em}</style>';
        $out[] = '<h1>Diagnostics</h1>';

        $baseUrl = $this->baseUrl();
        $out[] = '<p><strong>Base URL:</strong> <code>' . htmlspecialchars($baseUrl) . '</code></p>';

        $drivers = class_exists('PDO') ? \PDO::getAvailableDrivers() : [];
        $out[] = '<h2>PHP</h2><ul>';
        $out[] = '<li>PHP version: <code>' . PHP_VERSION . '</code></li>';
        $out[] = '<li>PDO drivers: <code>' . htmlspecialchars(implode(', ', $drivers)) . '</code></li>';
        $out[] = '<li>pdo_sqlite enabled: <code>' . (in_array('sqlite', $drivers, true) ? 'yes' : 'no') . '</code></li>';
        $out[] = '</ul>';

        $dbPath = $this->dbPath;
        $dir = dirname($dbPath);
        $out[] = '<h2>Database</h2><ul>';
        $out[] = '<li>DB path: <code>' . htmlspecialchars($dbPath) . '</code></li>';
        $out[] = '<li>DB realpath: <code>' . htmlspecialchars((string)@realpath($dbPath)) . '</code></li>';
        $out[] = '<li>DB exists: <code>' . (file_exists($dbPath) ? 'yes' : 'no') . '</code></li>';
        $out[] = '<li>DB size: <code>' . (file_exists($dbPath) ? filesize($dbPath) . ' bytes' : 'n/a') . '</code></li>';
        $out[] = '<li>Data dir: <code>' . htmlspecialchars($dir) . '</code></li>';
        $out[] = '<li>Data dir writable: <code>' . (is_writable($dir) ? 'yes' : 'no') . '</code></li>';
        $out[] = '</ul>';

        try {
            $pdo = $this->db();
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            $out[] = '<p><strong>Tables:</strong> <code>' . htmlspecialchars(implode(', ', $tables)) . '</code></p>';
            $countLists = (int)$pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn();
            $countItems = (int)$pdo->query('SELECT COUNT(*) FROM list_items')->fetchColumn();
            $out[] = '<p>Counts → lists: <code>' . $countLists . '</code>, list_items: <code>' . $countItems . '</code></p>';
        } catch (\Throwable $e) {
            $out[] = '<p style="color:#c00"><strong>DB error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        }

        $out[] = '<p><a href="?">Till startsidan</a></p>';
        echo implode("\n", $out);
    }
}
