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

namespace OCA\AdminOffboard\Operation;

use OCA\AdminOffboard\Logger\AppLogger;
use OCA\AdminOffboard\Audit\AuditLogger;

/**
 * Base operation class with common functionality
 */
abstract class BaseOperation implements OperationInterface
{
    protected const VERSION = '1.0.0';

    public function __construct(
        protected AppLogger $logger,
        protected AuditLogger $auditLogger
    ) {
    }

    public function getVersion(): string
    {
        return static::VERSION;
    }

    public function supportsDryRun(): bool
    {
        return true;
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function isIdempotent(): bool
    {
        return true;
    }

    public function getRequiredParams(): array
    {
        return [];
    }

    public function getOptionalParams(): array
    {
        return [];
    }

    /**
     * Log operation start
     */
    protected function logStart(string $operation, array $context): void
    {
        $this->logger->info("Starting operation: $operation", [
            'operation' => $operation,
            'context' => $context
        ]);
    }

    /**
     * Log operation completion
     */
    protected function logComplete(string $operation, array $result): void
    {
        $this->logger->info("Completed operation: $operation", [
            'operation' => $operation,
            'result' => $result
        ]);
    }

    /**
     * Log operation error
     */
    protected function logError(string $operation, \Exception $e, array $context): void
    {
        $this->logger->error("Operation failed: $operation", [
            'operation' => $operation,
            'error' => $e->getMessage(),
            'context' => $context,
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Validate required parameters
     */
    protected function validateRequiredParams(array $context, array $required): bool
    {
        foreach ($required as $param) {
            if (!isset($context[$param]) || empty($context[$param])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Merge optional parameters with defaults
     */
    protected function mergeOptionalParams(array $context, array $defaults): array
    {
        foreach ($defaults as $key => $default) {
            if (!isset($context[$key])) {
                $context[$key] = $default;
            }
        }
        return $context;
    }
}