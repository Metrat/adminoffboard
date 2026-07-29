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

namespace OCA\AdminOffboard\DTO;

/**
 * Remote wipe request DTO
 */
class RemoteWipeRequest
{
    public ?string $deviceId;
    public bool $dryRun;
    public bool $queue;

    public function __construct(array $data)
    {
        $this->deviceId = $data['device_id'] ?? null;
        $this->dryRun = (bool)($data['dry_run'] ?? false);
        $this->queue = (bool)($data['queue'] ?? false);
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'dry_run' => $this->dryRun,
            'queue' => $this->queue
        ];
    }

    public function validate(): void
    {
        // All fields have defaults, no validation needed
    }
}