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

use OCA\AdminOffboard\Audit\AuditLogger;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Audit logging middleware for API requests
 */
class AuditMiddleware extends Middleware
{
    public function __construct(
        private AuditLogger $auditLogger,
        private IRequest $request,
        private IUserSession $userSession
    ) {
    }

    /**
     * Log API request after controller execution
     */
    public function afterController($controller, $methodName, Response $response): Response
    {
        // Skip audit for non-API endpoints
        if (!str_starts_with($this->request->getPathInfo(), '/api/')) {
            return $response;
        }

        $user = $this->userSession->getUser();
        if (!$user) {
            return $response;
        }

        // Log API access
        $this->auditLogger->log(
            'api_access',
            $user->getUID(),
            'api',
            [
                'method' => $this->request->getMethod(),
                'path' => $this->request->getPathInfo(),
                'params' => $this->request->getParams(),
                'status' => $response->getStatus()
            ],
            $response->getStatus() < 400 ? 'success' : 'failure'
        );

        return $response;
    }

    /**
     * Handle exceptions
     */
    public function afterException($controller, $methodName, \Exception $exception): void
    {
        $user = $this->userSession->getUser();
        if (!$user) {
            return;
        }

        // Log API error
        $this->auditLogger->log(
            'api_error',
            $user->getUID(),
            'api',
            [
                'method' => $this->request->getMethod(),
                'path' => $this->request->getPathInfo(),
                'error' => $exception->getMessage(),
                'code' => $exception->getCode()
            ],
            'failure'
        );
    }
}