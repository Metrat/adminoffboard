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
 * @method string getJobType()
 * @method void setJobType(string $jobType)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method array getPayload()
 * @method void setPayload(array $payload)
 * @method string|null getUserId()
 * @method void setUserId(?string $userId)
 * @method string|null getCreatedBy()
 * @method void setCreatedBy(?string $createdBy)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int|null getStartedAt()
 * @method void setStartedAt(?int $startedAt)
 * @method int|null getCompletedAt()
 * @method void setCompletedAt(?int $completedAt)
 * @method int getAttempts()
 * @method void setAttempts(int $attempts)
 * @method int getMaxAttempts()
 * @method void setMaxAttempts(int $maxAttempts)
 * @method string|null getErrorMessage()
 * @method void setErrorMessage(?string $errorMessage)
 * @method int getPriority()
 * @method void setPriority(int $priority)
 */
class Job extends Entity
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_OFFBOARD = 'offboard';
    public const TYPE_DISABLE_USERS = 'disable_users';
    public const TYPE_DELETE_TOKENS = 'delete_tokens';
    public const TYPE_REMOTE_WIPE = 'remote_wipe';

    /** @var int */
    protected $id;

    /** @var string */
    protected $jobType;

    /** @var string */
    protected $status;

    /** @var array */
    protected $payload;

    /** @var string|null */
    protected $userId;

    /** @var string|null */
    protected $createdBy;

    /** @var int */
    protected $createdAt;

    /** @var int|null */
    protected $startedAt;

    /** @var int|null */
    protected $completedAt;

    /** @var int */
    protected $attempts;

    /** @var int */
    protected $maxAttempts;

    /** @var string|null */
    protected $errorMessage;

    /** @var int */
    protected $priority;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('jobType', 'string');
        $this->addType('status', 'string');
        $this->addType('payload', 'json');
        $this->addType('userId', 'string');
        $this->addType('createdBy', 'string');
        $this->addType('createdAt', 'integer');
        $this->addType('startedAt', 'integer');
        $this->addType('completedAt', 'integer');
        $this->addType('attempts', 'integer');
        $this->addType('maxAttempts', 'integer');
        $this->addType('errorMessage', 'string');
        $this->addType('priority', 'integer');
    }

    /**
     * Check if job is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if job is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if job is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if job has failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if job can be retried
     */
    public function canRetry(): bool
    {
        return $this->isFailed() && $this->attempts < $this->maxAttempts;
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    /**
     * Get job duration in seconds
     */
    public function getDuration(): ?int
    {
        if ($this->startedAt === null || $this->completedAt === null) {
            return null;
        }
        return $this->completedAt - $this->startedAt;
    }
}