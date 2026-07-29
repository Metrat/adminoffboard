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

namespace OCA\AdminOffboard\Validator;

use OCA\AdminOffboard\Exception\ValidationException;

/**
 * Operation validator
 */
class OperationValidator
{
    /**
     * Validate batch size
     *
     * @throws ValidationException
     */
    public function validateBatchSize(int $size, int $max = 1000): void
    {
        if ($size <= 0) {
            throw new ValidationException('Batch size must be greater than 0');
        }

        if ($size > $max) {
            throw new ValidationException("Batch size cannot exceed $max");
        }
    }

    /**
     * Validate priority
     *
     * @throws ValidationException
     */
    public function validatePriority(int $priority): void
    {
        if ($priority < 1 || $priority > 10) {
            throw new ValidationException('Priority must be between 1 and 10');
        }
    }

    /**
     * Validate dry run mode
     */
    public function validateDryRun(bool $dryRun): void
    {
        // No validation needed, just pass through
    }

    /**
     * Validate queue mode
     */
    public function validateQueue(bool $queue): void
    {
        // No validation needed, just pass through
    }

    /**
     * Validate operation parameters
     *
     * @throws ValidationException
     */
    public function validateParameters(array $params, array $required): void
    {
        foreach ($required as $key) {
            if (!isset($params[$key]) || empty($params[$key])) {
                throw new ValidationException("Required parameter '$key' is missing or empty");
            }
        }
    }

    /**
     * Validate operation type
     *
     * @throws ValidationException
     */
    public function validateOperationType(string $type): void
    {
        $validTypes = ['offboard', 'disable_users', 'delete_tokens', 'remote_wipe'];
        if (!in_array($type, $validTypes)) {
            throw new ValidationException("Invalid operation type. Must be one of: " . implode(', ', $validTypes));
        }
    }
}