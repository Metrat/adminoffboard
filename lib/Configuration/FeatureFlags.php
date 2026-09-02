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

/**
 * Feature flags for the application
 */
class FeatureFlags
{
    private array $features = [];

    public function __construct(
        private AppConfig $config
    ) {
        $this->initialize();
    }

    /**
     * Initialize feature flags
     */
    private function initialize(): void
    {
        $this->features = [
            'remote_wipe' => $this->config->getBool('feature_remote_wipe', true),
            'batch_operations' => $this->config->getBool('feature_batch_operations', true),
            'audit_logging' => $this->config->getBool('feature_audit_logging', true),
            'api_access' => $this->config->getBool('feature_api_access', true),
            'dry_run' => $this->config->getBool('feature_dry_run', true),
            'queue_system' => $this->config->getBool('feature_queue_system', true),
            'auto_cleanup' => $this->config->getBool('feature_auto_cleanup', true),
            'activity_logging' => $this->config->getBool('feature_activity_logging', true),
        ];
    }

    /**
     * Check if a feature is enabled
     */
    public function isEnabled(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    /**
     * Enable a feature
     */
    public function enable(string $feature): void
    {
        if (isset($this->features[$feature])) {
            $this->features[$feature] = true;
            $this->config->set('feature_' . $feature, 'true');
        }
    }

    /**
     * Disable a feature
     */
    public function disable(string $feature): void
    {
        if (isset($this->features[$feature])) {
            $this->features[$feature] = false;
            $this->config->set('feature_' . $feature, 'false');
        }
    }

    /**
     * Get all features
     */
    public function getAllFeatures(): array
    {
        return $this->features;
    }

    /**
     * Get enabled features
     */
    public function getEnabledFeatures(): array
    {
        return array_filter($this->features);
    }

    /**
     * Check if remote wipe is available
     */
    public function isRemoteWipeAvailable(): bool
    {
        return $this->isEnabled('remote_wipe');
    }

    /**
     * Check if batch operations are available
     */
    public function areBatchOperationsAvailable(): bool
    {
        return $this->isEnabled('batch_operations');
    }

    /**
     * Check if audit logging is enabled
     */
    public function isAuditLoggingEnabled(): bool
    {
        return $this->isEnabled('audit_logging');
    }

    /**
     * Check if API access is enabled
     */
    public function isApiAccessEnabled(): bool
    {
        return $this->isEnabled('api_access');
    }

    /**
     * Check if dry run is available
     */
    public function isDryRunAvailable(): bool
    {
        return $this->isEnabled('dry_run');
    }

    /**
     * Check if queue system is enabled
     */
    public function isQueueSystemEnabled(): bool
    {
        return $this->isEnabled('queue_system');
    }
}