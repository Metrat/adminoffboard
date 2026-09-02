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

namespace OCA\AdminOffboard\Middleware;

use OCA\AdminOffboard\Response\ApiResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Rate limiting middleware
 */
class RateLimitMiddleware extends Middleware
{
    private const RATE_LIMIT = 60; // requests per minute
    private const RATE_WINDOW = 60; // seconds

    private ICache $cache;

    public function __construct(
        ICacheFactory $cacheFactory,
        private IRequest $request,
        private IUserSession $userSession
    ) {
        $this->cache = $cacheFactory->createLocal('adminoffboard_rate_limit');
    }

    /**
     * Check rate limit before controller
     */
    public function beforeController($controller, $methodName): void
    {
        // Skip rate limiting for non-API endpoints
        if (!str_starts_with($this->request->getPathInfo(), '/api/')) {
            return;
        }

        $user = $this->userSession->getUser();
        if (!$user) {
            return;
        }

        $userId = $user->getUID();
        $key = "rate_limit_{$userId}";

        $current = (int)$this->cache->get($key);
        if ($current >= self::RATE_LIMIT) {
            throw new \Exception('Rate limit exceeded. Please try again later.', 429);
        }

        $this->cache->set($key, $current + 1, self::RATE_WINDOW);
    }

    /**
     * Handle exceptions
     */
    public function afterException($controller, $methodName, \Exception $exception): JSONResponse
    {
        if ($exception->getCode() === 429) {
            return ApiResponse::error($exception->getMessage(), 429);
        }

        // Re-throw if not rate limit related
        throw $exception;
    }
}