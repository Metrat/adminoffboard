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

namespace OCA\AdminOffboard\Migration;

use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

/**
 * Installation repair step
 */
class InstallRepairStep implements IRepairStep
{
    public function getName(): string
    {
        return 'AdminOffboard Install Repair Step';
    }

    public function run(IOutput $output): void
    {
        $output->info('Initializing AdminOffboard installation...');
        // Create directories, set permissions, etc.
    }
}

/**
 * Uninstallation repair step
 */
class UninstallRepairStep implements IRepairStep
{
    public function getName(): string
    {
        return 'AdminOffboard Uninstall Repair Step';
    }

    public function run(IOutput $output): void
    {
        $output->info('Cleaning up AdminOffboard installation...');
        // Cleanup tasks
    }
}

/**
 * Pre-migration repair step
 */
class PreMigrationRepairStep implements IRepairStep
{
    public function getName(): string
    {
        return 'AdminOffboard Pre-Migration Repair Step';
    }

    public function run(IOutput $output): void
    {
        $output->info('Preparing for migration...');
        // Pre-migration tasks
    }
}

/**
 * Post-migration repair step
 */
class PostMigrationRepairStep implements IRepairStep
{
    public function getName(): string
    {
        return 'AdminOffboard Post-Migration Repair Step';
    }

    public function run(IOutput $output): void
    {
        $output->info('Completing migration...');
        // Post-migration tasks
    }
}