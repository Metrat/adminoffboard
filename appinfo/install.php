<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use OCP\Util;

// Register scripts and styles for the app
Util::addScript('adminoffboard', 'adminoffboard-main');
Util::addStyle('adminoffboard', 'adminoffboard-main');

// Register initial settings
\OC::$server->getConfig()->setAppValue('adminoffboard', 'installed_version', '0.1.0');
\OC::$server->getConfig()->setAppValue('adminoffboard', 'installed_time', (string)time());

// Create initial database tables
$schema = \OC::$server->getDatabaseConnection()->getSchema();
// Tables will be created via migration