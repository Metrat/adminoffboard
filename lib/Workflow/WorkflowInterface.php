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

namespace OCA\AdminOffboard\Workflow;

/**
 * Workflow interface
 */
interface WorkflowInterface
{
    /**
     * Get workflow name
     */
    public function getName(): string;

    /**
     * Get workflow description
     */
    public function getDescription(): string;

    /**
     * Get workflow steps
     */
    public function getSteps(): array;

    /**
     * Execute the workflow
     */
    public function execute(array $context): array;

    /**
     * Validate workflow context
     */
    public function validateContext(array $context): bool;

    /**
     * Get required parameters
     */
    public function getRequiredParams(): array;

    /**
     * Get optional parameters
     */
    public function getOptionalParams(): array;

    /**
     * Check if dry run is supported
     */
    public function supportsDryRun(): bool;

    /**
     * Check if queuing is supported
     */
    public function supportsQueue(): bool;

    /**
     * Get workflow version
     */
    public function getVersion(): string;
}