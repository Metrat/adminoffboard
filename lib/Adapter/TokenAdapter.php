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

use OCP\Security\ISecureRandom;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Token management adapter using OCP API
 */
class TokenAdapter
{
    private const TOKEN_TABLE = 'authtoken';

    public function __construct(
        private ISecureRandom $secureRandom,
        private IDBConnection $db
    ) {
    }

    /**
     * Delete all tokens for a user
     */

    /**
     * Delete all tokens except current session
     */
    public function deleteAllTokensExceptCurrent(string $userId): bool
    {
        // Placeholder - needs implementation
        return $this->deleteAllTokens($userId);
    }
    public function deleteAllTokens(string $userId): bool
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TOKEN_TABLE)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)));

        return $qb->executeStatement() > 0;
    }

    /**
     * Delete specific token
     */
    public function deleteToken(int $tokenId): bool
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TOKEN_TABLE)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($tokenId, IQueryBuilder::PARAM_INT)));

        return $qb->executeStatement() > 0;
    }

    /**
     * Get tokens for a user
     */
    public function getUserTokens(string $userId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TOKEN_TABLE)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)));

        $result = $qb->executeStatement();
        $tokens = $result->fetchAll();
        $result->closeCursor();

        return $tokens;
    }

    /**
     * Get token by ID
     */
    public function getToken(int $tokenId): ?array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TOKEN_TABLE)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($tokenId, IQueryBuilder::PARAM_INT)));

        $result = $qb->executeStatement();
        $token = $result->fetch();
        $result->closeCursor();

        return $token ?: null;
    }

    /**
     * Count tokens for a user
     */
    public function countUserTokens(string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from(self::TOKEN_TABLE)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)));

        $result = $qb->executeStatement();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Get token by user and token ID
     */
    public function getUserToken(string $userId, int $tokenId): ?array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TOKEN_TABLE)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($tokenId, IQueryBuilder::PARAM_INT)));

        $result = $qb->executeStatement();
        $token = $result->fetch();
        $result->closeCursor();

        return $token ?: null;
    }

    /**
     * Delete tokens older than specified date
     */
    public function deleteOldTokens(int $timestamp): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(self::TOKEN_TABLE)
            ->where($qb->expr()->lt('last_activity', $qb->createNamedParameter($timestamp)));

        return $qb->executeStatement();
    }
}