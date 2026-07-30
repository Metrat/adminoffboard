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
 * @method string getAction()
 * @method void setAction(string $action)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getActor()
 * @method void setActor(string $actor)
 * @method string|null getTarget()
 * @method void setTarget(?string $target)
 * @method array|null getDetails()
 * @method void setDetails(?array $details)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method string|null getIpAddress()
 * @method void setIpAddress(?string $ipAddress)
 * @method string|null getUserAgent()
 * @method void setUserAgent(?string $userAgent)
 * @method int getTimestamp()
 * @method void setTimestamp(int $timestamp)
 */
class AuditLog extends Entity
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_PARTIAL = 'partial';

    public const ACTION_OFFBOARD = 'offboard';
    public const ACTION_DISABLE_USERS = 'disable_users';
    public const ACTION_DELETE_TOKENS = 'delete_tokens';
    public const ACTION_REMOTE_WIPE = 'remote_wipe';
    public const ACTION_QUEUE_PROCESS = 'queue_process';
    public const ACTION_CONFIG_CHANGE = 'config_change';

    /** @var int */
    public $id;

    /** @var string */
    protected $action;

    /** @var string */
    protected $userId;

    /** @var string */
    protected $actor;

    /** @var string|null */
    protected $target;

    /** @var array|null */
    protected $details;

    /** @var string */
    protected $status;

    /** @var string|null */
    protected $ipAddress;

    /** @var string|null */
    protected $userAgent;

    /** @var int */
    protected $timestamp;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('action', 'string');
        $this->addType('userId', 'string');
        $this->addType('actor', 'string');
        $this->addType('target', 'string');
        $this->addType('details', 'json');
        $this->addType('status', 'string');
        $this->addType('ipAddress', 'string');
        $this->addType('userAgent', 'string');
        $this->addType('timestamp', 'integer');
    }

    /**
     * Check if audit log entry is successful
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if audit log entry is a failure
     */
    public function isFailure(): bool
    {
        return $this->status === self::STATUS_FAILURE;
    }
}