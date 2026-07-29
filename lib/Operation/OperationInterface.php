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

/**
 * Operation interface for all operations
 */
interface OperationInterface
{
    /**
     * Get operation name
     */
    public function getName(): string;

    /**
     * Get operation description
     */
    public function getDescription(): string;

    /**
     * Execute the operation
     */
    public function execute(array $context): array;

    /**
     * Validate operation context
     */
    public function validateContext(array $context): bool;

    /**
     * Get required parameters
     */
    public function getRequiredParams(): array;

    /**
     * Get optional parameters with defaults
     */
    public function getOptionalParams(): array;

    /**
     * Check if operation supports dry run
     */
    public function supportsDryRun(): bool;

    /**
     * Check if operation supports queuing
     */
    public function supportsQueue(): bool;

    /**
     * Check if operation is idempotent
     */
    public function isIdempotent(): bool;

    /**
     * Get operation version
     */
    public function getVersion(): string;

    /**
     * Estimate operation impact (number of affected items)
     */
    public function estimateImpact(array $context): int;
}