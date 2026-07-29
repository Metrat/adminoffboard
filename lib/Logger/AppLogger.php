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

namespace OCA\AdminOffboard\Logger;

use OCP\AppFramework\Services\Logger;

/**
 * Application logger with additional context support
 */
class AppLogger extends Logger
{
    private array $context = [];

    /**
     * Set additional context for all log entries
     */
    public function withContext(array $context): self
    {
        $logger = clone $this;
        $logger->context = array_merge($this->context, $context);
        return $logger;
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        parent::debug($message, array_merge($this->context, $context));
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        parent::info($message, array_merge($this->context, $context));
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        parent::warning($message, array_merge($this->context, $context));
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        parent::error($message, array_merge($this->context, $context));
    }

    /**
     * Log a fatal message
     */
    public function fatal(string $message, array $context = []): void
    {
        parent::fatal($message, array_merge($this->context, $context));
    }

    /**
     * Log a metric (for monitoring)
     */
    public function metric(string $name, float $value, array $labels = []): void
    {
        $this->debug("Metric: $name = $value", [
            'metric' => $name,
            'value' => $value,
            'labels' => $labels
        ]);
    }

    /**
     * Log start of an operation
     */
    public function startOperation(string $operation, array $context = []): float
    {
        $startTime = microtime(true);
        $this->info("Starting operation: $operation", array_merge($context, ['operation' => $operation]));
        return $startTime;
    }

    /**
     * Log end of an operation
     */
    public function endOperation(string $operation, float $startTime, array $context = []): void
    {
        $duration = microtime(true) - $startTime;
        $this->info("Completed operation: $operation", array_merge($context, [
            'operation' => $operation,
            'duration_seconds' => round($duration, 3)
        ]));
    }
}