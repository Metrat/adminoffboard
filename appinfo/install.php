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

use OCP\IConfig;
use OCP\Util;
use OCP\Migration\IOutput;

// Register scripts and styles
Util::addScript('adminoffboard', 'adminoffboard');
Util::addStyle('adminoffboard', 'adminoffboard');

/**
 * Installation handler
 */
class AdminOffboardInstall {
    private IConfig $config;

    public function __construct() {
        $this->config = \OC::$server->get(IConfig::class);
    }

    public function run(): void {
        // Set initial configuration
        $this->config->setAppValue('adminoffboard', 'installed_version', '0.1.0');
        $this->config->setAppValue('adminoffboard', 'installed_time', (string)time());
        
        // Set default settings
        $defaults = [
            'queue_batch_size' => '100',
            'queue_max_attempts' => '3',
            'audit_log_retention_days' => '90',
            'dry_run_default' => 'false',
            'auto_cleanup_audit_logs' => 'true',
            'remote_wipe_timeout' => '30',
            'max_users_per_operation' => '1000',
            'allow_api_access' => 'true',
            'log_level' => 'info',
        ];

        foreach ($defaults as $key => $value) {
            if ($this->config->getAppValue('adminoffboard', $key, '') === '') {
                $this->config->setAppValue('adminoffboard', $key, $value);
            }
        }
    }
}

// Execute installation
$install = new AdminOffboardInstall();
$install->run();