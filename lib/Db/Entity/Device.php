<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Db\Entity;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getTokenId()
 * @method void setTokenId(int $tokenId)
 * @method string|null getDeviceType()
 * @method void setDeviceType(?string $deviceType)
 * @method string|null getDeviceName()
 * @method void setDeviceName(?string $deviceName)
 * @method int|null getLastActivity()
 * @method void setLastActivity(?int $lastActivity)
 * @method bool getWipeSupported()
 * @method void setWipeSupported(bool $wipeSupported)
 * @method string|null getWipeStatus()
 * @method void setWipeStatus(?string $wipeStatus)
 * @method int|null getWipeRequestedAt()
 * @method void setWipeRequestedAt(?int $wipeRequestedAt)
 * @method int|null getWipeCompletedAt()
 * @method void setWipeCompletedAt(?int $wipeCompletedAt)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class Device extends Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $userId;

    /** @var int */
    public $tokenId;

    /** @var string|null */
    public $deviceType;

    /** @var string|null */
    public $deviceName;

    /** @var int|null */
    public $lastActivity;

    /** @var bool */
    public $wipeSupported;

    /** @var string|null */
    public $wipeStatus;

    /** @var int|null */
    public $wipeRequestedAt;

    /** @var int|null */
    public $wipeCompletedAt;

    /** @var int */
    public $createdAt;

    /** @var int */
    public $updatedAt;

    public const WIPE_STATUS_PENDING = 'pending';
    public const WIPE_STATUS_IN_PROGRESS = 'in_progress';
    public const WIPE_STATUS_COMPLETED = 'completed';
    public const WIPE_STATUS_FAILED = 'failed';

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('userId', 'string');
        $this->addType('tokenId', 'integer');
        $this->addType('deviceType', 'string');
        $this->addType('deviceName', 'string');
        $this->addType('lastActivity', 'integer');
        $this->addType('wipeSupported', 'boolean');
        $this->addType('wipeStatus', 'string');
        $this->addType('wipeRequestedAt', 'integer');
        $this->addType('wipeCompletedAt', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function isActive(): bool
    {
        if ($this->lastActivity === null) {
            return false;
        }
        return (time() - $this->lastActivity) < (7 * 24 * 60 * 60);
    }

    public function isWipeSupported(): bool
    {
        return $this->wipeSupported;
    }

    public function isWipePending(): bool
    {
        return $this->wipeStatus === self::WIPE_STATUS_PENDING;
    }

    public function isWipeCompleted(): bool
    {
        return $this->wipeStatus === self::WIPE_STATUS_COMPLETED;
    }

    public function getAgeInDays(): int
    {
        return (int)((time() - $this->createdAt) / (24 * 60 * 60));
    }

    public function getWipeAgeInSeconds(): ?int
    {
        if ($this->wipeRequestedAt === null) {
            return null;
        }
        return time() - $this->wipeRequestedAt;
    }
}
