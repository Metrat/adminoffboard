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

namespace OCA\AdminOffboard\Controller;

use OCA\AdminOffboard\Service\OffboardService;
use OCA\AdminOffboard\Service\UserManagementService;
use OCA\AdminOffboard\Service\TokenManagementService;
use OCA\AdminOffboard\Service\RemoteWipeService;
use OCA\AdminOffboard\Service\AuditService;
use OCP\AppFramework\OCSController as BaseOCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * OCS Controller for Nextcloud API compatibility
 */
class OCSController extends BaseOCSController
{
    public function __construct(
        string $appName,
        IRequest $request,
        private OffboardService $offboardService,
        private UserManagementService $userManagementService,
        private TokenManagementService $tokenManagementService,
        private RemoteWipeService $remoteWipeService,
        private AuditService $auditService,
        private IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * OCS endpoint for offboarding
     */
    public function offboard(string $userId): array
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $remoteWipe = (bool)($params['remote_wipe'] ?? false);
            $dryRun = (bool)($params['dry_run'] ?? false);
            $queue = (bool)($params['queue'] ?? false);
            $actor = $this->getCurrentUser();

            $result = $this->offboardService->offboardUser(
                $userId,
                $remoteWipe,
                $dryRun,
                $queue,
                $actor
            );

            return [
                'status' => 'ok',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * OCS endpoint for getting user stats
     */
    public function userStats(): array
    {
        try {
            $this->ensureAdmin();

            $stats = $this->userManagementService->getUserStats();
            return [
                'status' => 'ok',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * OCS endpoint for getting audit logs
     */
    public function auditLogs(): array
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userId = $params['user_id'] ?? null;
            $actor = $params['actor'] ?? null;
            $action = $params['action'] ?? null;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

            $logs = $this->auditService->getLogs(
                $userId,
                $actor,
                $action,
                null,
                null,
                $limit,
                $offset
            );

            return [
                'status' => 'ok',
                'data' => $logs
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * OCS endpoint for disabling users
     */
    public function disableUsers(): array
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userIds = $params['user_ids'] ?? [];
            
            if (empty($userIds) || !is_array($userIds)) {
                return [
                    'status' => 'error',
                    'message' => 'User IDs are required and must be an array'
                ];
            }

            $dryRun = (bool)($params['dry_run'] ?? false);
            $queue = (bool)($params['queue'] ?? false);
            $actor = $this->getCurrentUser();

            $result = $this->userManagementService->disableUsers($userIds, $dryRun, $queue, $actor);

            return [
                'status' => 'ok',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * OCS endpoint for deleting tokens
     */
    public function deleteTokens(): array
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userIds = $params['user_ids'] ?? [];
            
            if (empty($userIds) || !is_array($userIds)) {
                return [
                    'status' => 'error',
                    'message' => 'User IDs are required and must be an array'
                ];
            }

            $dryRun = (bool)($params['dry_run'] ?? false);
            $queue = (bool)($params['queue'] ?? false);
            $actor = $this->getCurrentUser();

            $result = $this->tokenManagementService->deleteTokens($userIds, $dryRun, $queue, $actor);

            return [
                'status' => 'ok',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * OCS endpoint for remote wipe
     */
    public function remoteWipe(string $userId): array
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $deviceId = $params['device_id'] ?? null;
            $dryRun = (bool)($params['dry_run'] ?? false);
            $queue = (bool)($params['queue'] ?? false);
            $actor = $this->getCurrentUser();

            $result = $this->remoteWipeService->wipeUser($userId, $deviceId, $dryRun, $queue, $actor);

            return [
                'status' => 'ok',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Ensure user is admin
     *
     * @throws \Exception
     */
    private function ensureAdmin(): void
    {
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \Exception('Not authenticated', 401);
        }

        if (!$this->userSession->isLoggedIn()) {
            throw new \Exception('Not authenticated', 401);
        }

        $groupManager = \OC::$server->getGroupManager();
        $isAdmin = $groupManager->isAdmin($user->getUID());
        if (!$isAdmin) {
            throw new \Exception('Admin privileges required', 403);
        }
    }

    /**
     * Get current user
     */
    private function getCurrentUser(): string
    {
        $user = $this->userSession->getUser();
        return $user ? $user->getUID() : 'system';
    }
}