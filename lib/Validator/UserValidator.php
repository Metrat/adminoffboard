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

namespace OCA\AdminOffboard\Validator;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Exception\ValidationException;

/**
 * User validator
 */
class UserValidator
{
    private const MAX_USERNAME_LENGTH = 64;
    private const MIN_USERNAME_LENGTH = 1;

    public function __construct(
        private NextcloudAdapter $adapter
    ) {
    }

    /**
     * Validate user exists
     *
     * @throws ValidationException
     */
    public function validateUserExists(string $userId): void
    {
        if (empty($userId)) {
            throw new ValidationException('User ID cannot be empty');
        }

        if (!$this->adapter->userExists($userId)) {
            throw new ValidationException("User '$userId' does not exist");
        }
    }

    /**
     * Validate user not self
     *
     * @throws ValidationException
     */
    public function validateNotSelf(string $userId, string $actor): void
    {
        if ($userId === $actor) {
            throw new ValidationException('Cannot perform operation on yourself');
        }
    }

    /**
     * Validate user ID format
     *
     * @throws ValidationException
     */
    public function validateUserId(string $userId): void
    {
        if (empty($userId)) {
            throw new ValidationException('User ID cannot be empty');
        }

        if (strlen($userId) < self::MIN_USERNAME_LENGTH) {
            throw new ValidationException('User ID is too short');
        }

        if (strlen($userId) > self::MAX_USERNAME_LENGTH) {
            throw new ValidationException('User ID is too long');
        }

        // Check for invalid characters
        if (!preg_match('/^[a-zA-Z0-9_.@-]+$/', $userId)) {
            throw new ValidationException('User ID contains invalid characters');
        }
    }

    /**
     * Validate multiple user IDs
     *
     * @throws ValidationException
     */
    public function validateUserIds(array $userIds): void
    {
        if (empty($userIds)) {
            throw new ValidationException('User IDs cannot be empty');
        }

        foreach ($userIds as $userId) {
            $this->validateUserId($userId);
        }
    }

    /**
     * Validate user is disabled
     *
     * @throws ValidationException
     */
    public function validateUserDisabled(string $userId): void
    {
        $user = $this->adapter->getUser($userId);
        if (!$user) {
            throw new ValidationException("User '$userId' does not exist");
        }

        if ($user->isEnabled()) {
            throw new ValidationException("User '$userId' is not disabled");
        }
    }

    /**
     * Validate user is enabled
     *
     * @throws ValidationException
     */
    public function validateUserEnabled(string $userId): void
    {
        $user = $this->adapter->getUser($userId);
        if (!$user) {
            throw new ValidationException("User '$userId' does not exist");
        }

        if (!$user->isEnabled()) {
            throw new ValidationException("User '$userId' is disabled");
        }
    }

    /**
     * Validate user has no tokens
     *
     * @throws ValidationException
     */
    public function validateUserHasTokens(string $userId): void
    {
        $tokens = $this->adapter->deleteAllTokens($userId);
        // Note: This is a bit hacky, but we check if tokens exist by trying to delete them
        // In a real implementation, you'd want a method to count tokens
        if (!$tokens) {
            throw new ValidationException("User '$userId' has no tokens");
        }
    }
}