<?php

declare(strict_types=1);

define('PHPUNIT_RUN', 1);

require_once __DIR__ . '/../../../lib/base.php';

$appManager = \OCP\Server::get(\OCP\App\IAppManager::class);
$appManager->enableApp('adminoffboard');
\OC_App::loadApp('adminoffboard');
