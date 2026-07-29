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

/**
 * User management adapter
 */
class UserAdapter
{
    public function __construct(
        private IUserManager $userManager,
        private IUserSession $userSession
    ) {
    }

    /**
     * Disable a user
     */
    public function disableUser(string $userId): bool
    {
        $user = $this->userManager->get($userId);
        if (!$user) {
            return false;
        }

        // Prevent disabling yourself
        $currentUser = $this->userSession->getUser();
        if ($currentUser && $currentUser->getUID() === $userId) {
            throw new \RuntimeException('Cannot disable yourself');
        }

        $user->setEnabled(false);
        return true;
    }

    /**
     * Enable a user
     */
    public function enableUser(string $userId): bool
    {
        $user = $this->userManager->get($userId);
        if (!$user) {
            return false;
        }

        $user->setEnabled(true);
        return true;
    }

    /**
     * Check if user exists
     */
    public function userExists(string $userId): bool
    {
        return $this->userManager->userExists($userId);
    }

    /**
     * Get user by ID
     */
    public function getUser(string $userId): ?\OCP\IUser
    {
        return $this->userManager->get($userId);
    }

    /**
     * Get all users
     */
    public function getAllUsers(): array
    {
        return $this->userManager->search('');
    }

    /**
     * Get disabled users
     */
    public function getDisabledUsers(): array
    {
        $allUsers = $this->getAllUsers();
        return array_filter($allUsers, function ($user) {
            return !$user->isEnabled();
        });
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): array
    {
        $allUsers = $this->getAllUsers();
        return array_filter($allUsers, function ($user) {
            return $user->isEnabled();
        });
    }

    /**
     * Get users by search term
     */
    public function searchUsers(string $search, int $limit = 100): array
    {
        return $this->userManager->search($search, $limit);
    }
}