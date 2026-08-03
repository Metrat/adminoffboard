<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Db\Mapper;

use OCA\AdminOffboard\Db\Entity\Device;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * @extends QBMapper<Device>
 */
class DeviceMapper extends QBMapper
{
    private const TABLE_NAME = 'adminoffboard_devices';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLE_NAME, Device::class);
    }

    public function find(int $id): Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    public function findByUserAndToken(string $userId, int $tokenId): Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('token_id', $qb->createNamedParameter($tokenId, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    public function findByUserId(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('last_activity', 'DESC');

        return $this->findEntities($qb);
    }

    public function findByUserAndDeviceId(string $userId, string $deviceId): Device
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($deviceId, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    public function findByType(string $deviceType): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('device_type', $qb->createNamedParameter($deviceType)));

        return $this->findEntities($qb);
    }

    public function findWipeSupported(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_supported', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));

        return $this->findEntities($qb);
    }

    public function findPendingWipes(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_status', $qb->createNamedParameter(Device::WIPE_STATUS_PENDING)));

        return $this->findEntities($qb);
    }

    public function findFailedWipes(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_status', $qb->createNamedParameter(Device::WIPE_STATUS_FAILED)));

        return $this->findEntities($qb);
    }

    public function deleteByUser(string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $qb->executeStatement();
    }

    public function deleteOldDevices(int $timestamp): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->lt('last_activity', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)));

        return $qb->executeStatement();
    }

    public function countByUser(string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    public function countAll(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName());

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    public function countWipeSupported(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_supported', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    public function countActive(): int
    {
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->gt('last_activity', $qb->createNamedParameter($sevenDaysAgo, IQueryBuilder::PARAM_INT)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    public function countByType(string $deviceType): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('device_type', $qb->createNamedParameter($deviceType)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    public function countByWipeStatus(string $status): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('wipe_status', $qb->createNamedParameter($status)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }
}
