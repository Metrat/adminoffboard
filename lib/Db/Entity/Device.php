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
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class Device extends Entity
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $userId;

    /** @var int */
    protected $tokenId;

    /** @var string|null */
    protected $deviceType;

    /** @var string|null */
    protected $deviceName;

    /** @var int|null */
    protected $lastActivity;

    /** @var bool */
    protected $wipeSupported;

    /** @var int */
    protected $createdAt;

    /** @var int */
    protected $updatedAt;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('userId', 'string');
        $this->addType('tokenId', 'integer');
        $this->addType('deviceType', 'string');
        $this->addType('deviceName', 'string');
        $this->addType('lastActivity', 'integer');
        $this->addType('wipeSupported', 'boolean');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    /**
     * Check if device is active (activity within last 7 days)
     */
    public function isActive(): bool
    {
        if ($this->lastActivity === null) {
            return false;
        }
        return (time() - $this->lastActivity) < (7 * 24 * 60 * 60);
    }

    /**
     * Check if device supports remote wipe
     */
    public function isWipeSupported(): bool
    {
        return $this->wipeSupported;
    }

    /**
     * Get device age in days
     */
    public function getAgeInDays(): int
    {
        return (int)((time() - $this->createdAt) / (24 * 60 * 60));
    }
}