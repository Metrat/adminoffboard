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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * API authentication middleware
 */
class ApiAuthMiddleware extends Middleware
{
    public function __construct(
        private IUserSession $userSession,
        private IRequest $request
    ) {
    }

    /**
     * Check if request is authenticated
     */
    public function beforeController($controller, $methodName): void
    {
        // Skip authentication for public endpoints
        $publicEndpoints = [
            'ocs',
            'health',
            'version'
        ];

        $route = $this->request->getParam('_route');
        if (in_array($route, $publicEndpoints)) {
            return;
        }

        // Check if user is logged in
        if (!$this->userSession->isLoggedIn()) {
            throw new \Exception('Authentication required', 401);
        }

        // Check if user is admin
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \Exception('User not found', 401);
        }

        $isAdmin = \OC::$server->getGroupManager()->isAdmin($user->getUID());
        if (!$isAdmin) {
            throw new \Exception('Admin privileges required', 403);
        }
    }

    /**
     * Handle exceptions
     */
    public function afterException($controller, $methodName, \Exception $exception): JSONResponse
    {
        $status = $exception->getCode() ?: 500;
        
        if ($status === 401) {
            return ApiResponse::unauthorized($exception->getMessage());
        }
        
        if ($status === 403) {
            return ApiResponse::forbidden($exception->getMessage());
        }

        return ApiResponse::error($exception->getMessage(), $status);
    }
}