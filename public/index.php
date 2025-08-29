<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Controller\ListController;

$controller = new ListController();
$controller->handleRequest();
