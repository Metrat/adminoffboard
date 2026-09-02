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

namespace OCA\AdminOffboard\Configuration;

/**
 * Application constants
 */
final class Constants
{
    // Application
    public const APP_ID = 'adminoffboard';
    public const APP_VERSION = '0.1.0';
    public const APP_NAME = 'Admin Offboard';

    // Database tables
    public const TABLE_JOBS = 'adminoffboard_jobs';
    public const TABLE_AUDIT = 'adminoffboard_audit';
    public const TABLE_DEVICES = 'adminoffboard_devices';

    // Queue
    public const QUEUE_BATCH_SIZE = 100;
    public const QUEUE_MAX_ATTEMPTS = 3;
    public const QUEUE_RETRY_DELAY = 60;

    // Audit
    public const AUDIT_RETENTION_DAYS = 90;
    public const AUDIT_BATCH_SIZE = 1000;

    // Remote wipe
    public const REMOTE_WIPE_TIMEOUT = 30;
    public const MAX_USERS_PER_OPERATION = 1000;

    // Cache
    public const CACHE_DEVICE_TTL = 3600;

    // API
    public const API_VERSION = 'v1';
    public const API_RATE_LIMIT = 60; // requests per minute

    // Performance
    public const DEFAULT_PAGE_SIZE = 50;
    public const MAX_PAGE_SIZE = 500;

    // Security
    public const SESSION_TIMEOUT = 3600; // 1 hour
    public const TOKEN_LENGTH = 64;

    // Logging
    public const LOG_LEVEL_DEBUG = 'debug';
    public const LOG_LEVEL_INFO = 'info';
    public const LOG_LEVEL_WARNING = 'warning';
    public const LOG_LEVEL_ERROR = 'error';
    public const LOG_LEVEL_FATAL = 'fatal';

    // Job priorities
    public const PRIORITY_LOW = 1;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_HIGH = 9;
    public const PRIORITY_CRITICAL = 10;

    /**
     * Get all job priorities
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical'
        ];
    }

    /**
     * Get all job types
     */
    public static function getJobTypes(): array
    {
        return [
            'offboard' => 'Offboard User',
            'disable_users' => 'Disable Users',
            'delete_tokens' => 'Delete Tokens',
            'remote_wipe' => 'Remote Wipe'
        ];
    }

    /**
     * Get all audit actions
     */
    public static function getAuditActions(): array
    {
        return [
            'offboard' => 'User Offboard',
            'disable_users' => 'Mass User Disable',
            'delete_tokens' => 'Mass Token Deletion',
            'remote_wipe' => 'Remote Wipe',
            'queue_process' => 'Queue Processing',
            'config_change' => 'Configuration Change'
        ];
    }
}