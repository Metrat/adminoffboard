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

namespace OCA\AdminOffboard\Configuration;

use OCP\IConfig;

/**
 * Application configuration manager
 */
class AppConfig
{
    private const DEFAULT_VALUES = [
        'queue_batch_size' => '100',
        'queue_max_attempts' => '3',
        'queue_retry_delay' => '60',
        'audit_log_retention_days' => '90',
        'dry_run_default' => 'false',
        'auto_cleanup_audit_logs' => 'true',
        'remote_wipe_timeout' => '30',
        'max_users_per_operation' => '1000',
        'allow_api_access' => 'true',
        'log_level' => 'info',
    ];

    public function __construct(
        private IConfig $config,
        private string $appId
    ) {
    }

    /**
     * Initialize configuration with default values
     */
    public function init(): void
    {
        foreach (self::DEFAULT_VALUES as $key => $defaultValue) {
            if ($this->config->getAppValue($this->appId, $key, '') === '') {
                $this->config->setAppValue($this->appId, $key, $defaultValue);
            }
        }
    }

    /**
     * Get configuration value
     */
    public function get(string $key, ?string $default = null): string
    {
        if ($default === null && isset(self::DEFAULT_VALUES[$key])) {
            $default = self::DEFAULT_VALUES[$key];
        }
        
        return $this->config->getAppValue(
            $this->appId,
            $key,
            $default ?? ''
        );
    }

    /**
     * Set configuration value
     */
    public function set(string $key, string $value): void
    {
        $this->config->setAppValue($this->appId, $key, $value);
    }

    /**
     * Get integer configuration value
     */
    public function getInt(string $key, ?int $default = null): int
    {
        return (int)$this->get($key, $default !== null ? (string)$default : null);
    }

    /**
     * Get boolean configuration value
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default !== null ? ($default ? 'true' : 'false') : null);
        return $value === 'true' || $value === '1';
    }

    /**
     * Get queue batch size
     */
    public function getQueueBatchSize(): int
    {
        return $this->getInt('queue_batch_size', 100);
    }

    /**
     * Get queue max attempts
     */
    public function getQueueMaxAttempts(): int
    {
        return $this->getInt('queue_max_attempts', 3);
    }

    /**
     * Get audit log retention in days
     */
    public function getAuditLogRetentionDays(): int
    {
        return $this->getInt('audit_log_retention_days', 90);
    }

    /**
     * Is dry run default
     */
    public function isDryRunDefault(): bool
    {
        return $this->getBool('dry_run_default', false);
    }

    /**
     * Is API access allowed
     */
    public function isApiAccessAllowed(): bool
    {
        return $this->getBool('allow_api_access', true);
    }

    /**
     * Get log level
     */
    public function getLogLevel(): string
    {
        return $this->get('log_level', 'info');
    }
}