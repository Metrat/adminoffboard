<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 */

// Define test environment
define('PHPUNIT_RUN', 1);

// Set timezone
date_default_timezone_set('UTC');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Setup Nextcloud test environment if available
if (file_exists(__DIR__ . '/../../../lib/versioncheck.php')) {
    require_once __DIR__ . '/../../../lib/base.php';
}