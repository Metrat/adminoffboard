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
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Database mapper for Device entities
 */
class DeviceMapper extends QBMapper
{
    public const TABLE_NAME = 'adminoffboard_devices';

    public function __construct(
        IDBConnection $db
    ) {
        parent::__construct($db, self::TABLE_NAME, Device::class);
    }

    /**
     * Find device by ID
     */
    public function findById(int $id): ?Device
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        
        return $this->findEntity($qb);
    }

    /**
     * Find devices by user ID
     */
    public function findByUserId(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        
        return $this->findEntities($qb);
    }

    /**
     * Find device by device token
     */
    public function findByDeviceToken(string $deviceToken): ?Device
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('device_token', $qb->createNamedParameter($deviceToken)));
        
        return $this->findEntity($qb);
    }

    /**
     * Find all devices
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        
        return $this->findEntities($qb);
    }

    /**
     * Find devices by status
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq('status', $qb->createNamedParameter($status)))
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        
        return $this->findEntities($qb);
    }

    /**
     * Update device status
     */
    public function updateStatus(int $id, string $status): void
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->update(self::TABLE_NAME)
            ->set('status', $qb->createNamedParameter($status))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
            ->executeStatement();
    }

    /**
     * Find device by user ID and device ID
     */
    public function findByUserAndDeviceId(string $userId, string $deviceId): ?Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select("*")
            ->from(self::TABLE_NAME)
            ->where($qb->expr()->eq("user_id", $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq("id", $qb->createNamedParameter($deviceId, IQueryBuilder::PARAM_INT)));
        return $this->findEntity($qb);
    }

    /**
     * Delete device by ID
     */
    public function deleteById(int $id): void
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->delete(self::TABLE_NAME)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
            ->executeStatement();
    }

    /**
     * Delete all devices for a user
     */
    public function deleteByUserId(string $userId): void
    {
        $qb = $this->db->getQueryBuilder();
        
        $qb->delete(self::TABLE_NAME)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->executeStatement();
    }
}