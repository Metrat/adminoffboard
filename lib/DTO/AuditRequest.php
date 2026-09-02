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
 * Audit request DTO
 */
class AuditRequest
{
    public ?string $userId;
    public ?string $actor;
    public ?string $action;
    public ?int $from;
    public ?int $to;
    public int $limit;
    public int $offset;

    public function __construct(array $data)
    {
        $this->userId = $data['user_id'] ?? null;
        $this->actor = $data['actor'] ?? null;
        $this->action = $data['action'] ?? null;
        $this->from = isset($data['from']) ? (int)$data['from'] : null;
        $this->to = isset($data['to']) ? (int)$data['to'] : null;
        $this->limit = isset($data['limit']) ? (int)$data['limit'] : 100;
        $this->offset = isset($data['offset']) ? (int)$data['offset'] : 0;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'actor' => $this->actor,
            'action' => $this->action,
            'from' => $this->from,
            'to' => $this->to,
            'limit' => $this->limit,
            'offset' => $this->offset
        ];
    }

    public function validate(): void
    {
        if ($this->limit > 1000) {
            throw new \InvalidArgumentException('Limit cannot exceed 1000');
        }

        if ($this->from && $this->to && $this->from > $this->to) {
            throw new \InvalidArgumentException('From date must be before to date');
        }
    }
}