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

namespace OCA\AdminOffboard\Db\Mapper;

use OCA\AdminOffboard\Db\Entity\Device;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Connection;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Database mapper for Device entities
 */
class DeviceMapper extends QBMapper
{
    public const TABLE_NAME = 'adminoffboard_devices';

    public function __construct(
        IDBConnection $db,
        private Connection $connection
    ) {
        parent::__construct($db, self::TABLE_NAME, Device::class);
    }

    /**
     * Find device by ID
     */
    public function find(int $id): Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * Find devices by user
     */
    public function findByUser(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('updated_at', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * Find device by user and token
     */
    public function findByUserAndToken(string $userId, int $tokenId): Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('token_id', $qb->createNamedParameter($tokenId, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * Find device by user and device ID (string)
     */
    public function findByUserAndDeviceId(string $userId, string $deviceId): Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($deviceId)));

        return $this->findEntity($qb);
    }

    /**
     * Find devices by type
     */
    public function findByType(string $deviceType): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('device_type', $qb->createNamedParameter($deviceType)))
            ->orderBy('updated_at', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * Find devices that support remote wipe
     */
    public function findWipeSupported(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_supported', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
            ->orderBy('updated_at', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * Delete devices by user
     */
    public function deleteByUser(string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $qb->execute();
    }

    /**
     * Delete old devices
     */
    public function deleteOldDevices(int $timestamp): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->lt('updated_at', $qb->createNamedParameter($timestamp)));

        return $qb->execute();
    }

    /**
     * Count devices by user
     */
    public function countByUser(string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count devices by type
     */
    public function countByType(string $deviceType): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('device_type', $qb->createNamedParameter($deviceType)));

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count total devices
     */
    public function countAll(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName());

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count devices that support remote wipe
     */
    public function countWipeSupported(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_supported', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)));

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count active devices (activity within last 7 days)
     */
    public function countActive(): int
    {
        $cutoff = time() - (7 * 24 * 60 * 60);
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->gte('last_activity', $qb->createNamedParameter($cutoff)));

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }
}