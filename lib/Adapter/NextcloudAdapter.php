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

namespace OCA\AdminOffboard\Adapter;

use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;

/**
 * Main Nextcloud adapter that orchestrates all operations
 */
class NextcloudAdapter
{
    public function __construct(
        private UserAdapter $userAdapter,
        private TokenAdapter $tokenAdapter,
        private DeviceAdapter $deviceAdapter
    ) {
    }

    /**
     * Disable a user
     */
    public function disableUser(string $userId): bool
    {
        return $this->userAdapter->disableUser($userId);
    }

    /**
     * Enable a user
     */
    public function enableUser(string $userId): bool
    {
        return $this->userAdapter->enableUser($userId);
    }

    /**
     * Check if user exists
     */
    public function userExists(string $userId): bool
    {
        return $this->userAdapter->userExists($userId);
    }

    /**
     * Get user by ID
     */
    public function getUser(string $userId): ?\OCP\IUser
    {
        return $this->userAdapter->getUser($userId);
    }

    /**
     * Get all users
     */
    public function getAllUsers(): array
    {
        return $this->userAdapter->getAllUsers();
    }

    /**
     * Search users with pagination
     */
    public function searchUsers(string $search = '', int $limit = 50, int $offset = 0): array
    {
        return $this->userAdapter->searchUsers($search, $limit, $offset);
    }

    /**
     * Delete all tokens for a user
     */
    public function deleteAllTokens(string $userId): bool
    {
        return $this->tokenAdapter->deleteAllTokens($userId);
    }


    /**
     * Alias for deleteAllTokens
     */
    public function deleteAllUserTokens(string $userId): bool
    {
        return $this->deleteAllTokens($userId);
    }

    /**
     * Delete all tokens except current session
     */
    public function deleteUserTokensExceptCurrent(string $userId): bool
    {
        return $this->tokenAdapter->deleteAllTokensExceptCurrent($userId);
    }

    /**
     * Remote wipe all devices for user
     */
    public function remoteWipeUserDevices(string $userId): int
    {
        return $this->deviceAdapter->remoteWipeUserDevices($userId);
    }
    /**
     * Delete specific token
     */
    public function deleteToken(int $tokenId): bool
    {
        return $this->tokenAdapter->deleteToken($tokenId);
    }

    /**
     * Get user's devices
     */
    public function getUserDevices(string $userId): array
    {
        return $this->deviceAdapter->getUserDevices($userId);
    }

    /**
     * Get device by ID
     */
    public function getDevice(int $deviceId): ?\OCA\AdminOffboard\Db\Entity\Device
    {
        return $this->deviceAdapter->getDevice($deviceId);
    }

    /**
     * Remote wipe a user's device
     */
    public function remoteWipeUser(string $userId, ?string $deviceId = null): bool
    {
        return $this->deviceAdapter->remoteWipeUser($userId, $deviceId);
    }

    /**
     * Remote wipe specific device
     */
    public function remoteWipeDevice(int $deviceId): bool
    {
        return $this->deviceAdapter->remoteWipeDevice($deviceId);
    }

    /**
     * Sync devices for a user
     */
    public function syncUserDevices(string $userId): array
    {
        return $this->deviceAdapter->syncUserDevices($userId);
    }

    /**
     * Check if remote wipe is supported for device
     */
    public function isRemoteWipeSupported(string $userId, int $tokenId): bool
    {
        return $this->deviceAdapter->isRemoteWipeSupported($userId, $tokenId);
    }
}