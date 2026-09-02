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
use OCA\AdminOffboard\Queue\QueueManager;
use OCA\AdminOffboard\DTO\OffboardRequest;
use OCA\AdminOffboard\DTO\DisableUsersRequest;
use OCA\AdminOffboard\DTO\DeleteTokensRequest;
use OCA\AdminOffboard\DTO\RemoteWipeRequest;
use OCA\AdminOffboard\DTO\AuditRequest;
use OCA\AdminOffboard\Response\ApiResponse;
use OCA\AdminOffboard\Exception\ValidationException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * API Controller for AdminOffboard
 */
class ApiController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private OffboardService $offboardService,
        private UserManagementService $userManagementService,
        private TokenManagementService $tokenManagementService,
        private RemoteWipeService $remoteWipeService,
        private AuditService $auditService,
        private QueueManager $queueManager,
        private IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Get users
     */
    public function getUsers(
        string $search = '',
        int $limit = 50,
        int $offset = 0,
        bool $includeDisabled = true
    ): JSONResponse {
        try {
            $this->ensureAdmin();

            $result = $this->userManagementService->getUsers(
                $search,
                $limit,
                $offset,
                $includeDisabled
            );

            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Get user statistics
     */
    /**
     * Get dashboard data
     */
    public function getDashboard(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $userStats = $this->userManagementService->getUserStats();
            $auditStats = $this->auditService->getStats();
            $queueStats = $this->queueManager->getStats();
            $recentActivity = $this->auditService->getRecentLogs(5);

            return ApiResponse::success([
                'users' => $userStats,
                'audit' => $auditStats,
                'queue' => $queueStats,
                'recentActivity' => $recentActivity,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getUserStats(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $stats = $this->userManagementService->getUserStats();
            return ApiResponse::success($stats);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Offboard a user
     */
    public function offboardUser(string $userId): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $request = new OffboardRequest($this->request->getParams());
            $actor = $this->getCurrentUser();

            $result = $this->offboardService->offboardUser(
                $userId,
                $request->remoteWipe,
                $request->dryRun,
                $request->queue,
                $actor
            );

            return ApiResponse::success($result);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Offboard multiple users
     */
    public function offboardUsers(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userIds = $params['user_ids'] ?? [];
            
            if (empty($userIds)) {
                return ApiResponse::error('User IDs are required', 400);
            }

            if (!is_array($userIds)) {
                return ApiResponse::error('User IDs must be an array', 400);
            }

            $remoteWipe = $params['remote_wipe'] ?? false;
            $dryRun = $params['dry_run'] ?? false;
            $queue = $params['queue'] ?? false;
            $actor = $this->getCurrentUser();

            $result = $this->offboardService->offboardUsers(
                $userIds,
                $remoteWipe,
                $dryRun,
                $queue,
                $actor
            );

            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Disable users
     */
    public function disableUsers(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userIds = $params['user_ids'] ?? [];
            
            if (empty($userIds)) {
                return ApiResponse::error('User IDs are required', 400);
            }

            if (!is_array($userIds)) {
                return ApiResponse::error('User IDs must be an array', 400);
            }

            $dryRun = $params['dry_run'] ?? false;
            $queue = $params['queue'] ?? false;
            $actor = $this->getCurrentUser();

            $result = $this->userManagementService->disableUsers(
                $userIds,
                $dryRun,
                $queue,
                $actor
            );

            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Delete tokens for users
     */
    public function deleteTokens(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userIds = $params['user_ids'] ?? [];
            
            if (empty($userIds)) {
                return ApiResponse::error('User IDs are required', 400);
            }

            if (!is_array($userIds)) {
                return ApiResponse::error('User IDs must be an array', 400);
            }

            $dryRun = $params['dry_run'] ?? false;
            $queue = $params['queue'] ?? false;
            $actor = $this->getCurrentUser();

            $result = $this->tokenManagementService->deleteTokens(
                $userIds,
                $dryRun,
                $queue,
                $actor
            );

            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Remote wipe a user's devices
     */
    public function remoteWipe(string $userId): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $deviceId = $params['device_id'] ?? null;
            $dryRun = $params['dry_run'] ?? false;
            $queue = $params['queue'] ?? false;
            $actor = $this->getCurrentUser();

            $result = $this->remoteWipeService->wipeUser(
                $userId,
                $deviceId,
                $dryRun,
                $queue,
                $actor
            );

            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Check if remote wipe is possible
     */
    public function canWipe(string $userId): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $result = $this->remoteWipeService->canWipeUser($userId);
            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Get audit logs
     */
    public function getAuditLogs(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $params = $this->request->getParams();
            $userId = $params['user_id'] ?? null;
            $actor = $params['actor'] ?? null;
            $action = $params['action'] ?? null;
            $from = isset($params['from']) ? (int)$params['from'] : null;
            $to = isset($params['to']) ? (int)$params['to'] : null;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

            $logs = $this->auditService->getLogs(
                $userId,
                $actor,
                $action,
                $from,
                $to,
                $limit,
                $offset
            );

            $count = $this->auditService->getLogCount($action, $userId);

            return ApiResponse::success([
                'logs' => $logs,
                'total' => $count,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Get audit statistics
     */
    public function getAuditStats(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $stats = $this->auditService->getStatistics();
            return ApiResponse::success($stats);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Get queue statistics
     */
    public function getQueueStats(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $stats = $this->queueManager->getStats();
            return ApiResponse::success($stats);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Get job status
     */
    public function getJobStatus(int $jobId): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $job = $this->queueManager->getJob($jobId);
            if (!$job) {
                return ApiResponse::error('Job not found', 404);
            }

            return ApiResponse::success([
                'id' => $job->getId(),
                'type' => $job->getJobType(),
                'status' => $job->getStatus(),
                'user_id' => $job->getUserId(),
                'created_by' => $job->getCreatedBy(),
                'created_at' => $job->getCreatedAt(),
                'started_at' => $job->getStartedAt(),
                'completed_at' => $job->getCompletedAt(),
                'attempts' => $job->getAttempts(),
                'max_attempts' => $job->getMaxAttempts(),
                'error_message' => $job->getErrorMessage(),
                'duration' => $job->getDuration()
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Cancel a job
     */
    public function cancelJob(int $jobId): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $result = $this->queueManager->cancelJob($jobId);
            if (!$result) {
                return ApiResponse::error('Failed to cancel job', 400);
            }

            return ApiResponse::success(['message' => 'Job cancelled successfully']);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @CORS
     * @NoCSRFRequired
     * 
     * Search audit logs
     */
    public function searchAudit(): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $search = $this->request->getParam('search', '');
            $limit = (int)$this->request->getParam('limit', 100);

            if (empty($search)) {
                return ApiResponse::error('Search term is required', 400);
            }

            $results = $this->auditService->searchLogs($search, $limit);
            return ApiResponse::success($results);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get install command for wipe agent
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getInstallCommand(string $userId): JSONResponse
    {
        $serverUrl = 'https://cloud9.pkflink.ru';
        
        $command = "curl.exe -k -s 'https://cloud9.pkflink.ru/index.php/apps/adminoffboard/api/v1/wipe-agent/install-script/" . $userId . "' -o \$env:TEMP\install-response.json" . PHP_EOL
            . "$json = Get-Content \$env:TEMP\install-response.json -Raw | ConvertFrom-Json" . PHP_EOL
            . "[Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($json.data.content)) | Out-File \$env:TEMP\install-wipe-agent.ps1 -Encoding UTF8" . PHP_EOL
            . "powershell -ExecutionPolicy Bypass -File \$env:TEMP\install-wipe-agent.ps1 -Username '" . $userId . "'";

        return ApiResponse::success([
            'command' => $command,
            'user_id' => $userId,
            'instructions' => 'Run this command in PowerShell on user\'s Windows computer. The agent will install automatically and run every 5 minutes.',
        ]);
    }

    /**
     * Deploy wipe agent script to user's Nextcloud folder
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deployWipeAgent(string $userId): JSONResponse
    {
        try {
            $this->ensureAdmin();

            $scriptPath = __DIR__ . '/../../tools/wipe-agent.ps1';
            if (!file_exists($scriptPath)) {
                return ApiResponse::error('Script not found', 404);
            }

            $scriptContent = file_get_contents($scriptPath);

            // Найти папку пользователя
            $userFolder = '/var/www/nextcloud/data/' . $userId . '/files';
            if (!is_dir($userFolder)) {
                return ApiResponse::error('User folder not found', 404);
            }

            // Создать файл скрипта в папке пользователя
            $targetFile = $userFolder . '/wipe-agent.ps1';
            file_put_contents($targetFile, $scriptContent);
            chmod($targetFile, 0644);
            chown($targetFile, 'www-data');

            // Update file scanner
            try {
                $command = '/usr/bin/php /var/www/nextcloud/occ files:scan ' . escapeshellarg($userId) . ' 2>&1';
                $output = shell_exec($command);
            } catch (\Exception $scanError) {
                // Ignore
            }

            return ApiResponse::success([
                'status' => 'deployed',
                'user_id' => $userId,
                'file' => 'wipe-agent.ps1',
                'path' => $userFolder . '/wipe-agent.ps1',
                'scanned' => true,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Download installer script
     * @PublicPage
     * @NoCSRFRequired
     */
    public function downloadInstaller(string $userId): JSONResponse
    {
        $scriptPath = __DIR__ . '/../../tools/install-wipe-agent.ps1';
        if (!file_exists($scriptPath)) {
            return ApiResponse::error('Script not found', 404);
        }

        $scriptContent = file_get_contents($scriptPath);
        $scriptContent = str_replace('$Username = ""', '$Username = "' . $userId . '"', $scriptContent);

        return ApiResponse::success([
            'filename' => 'install-wipe-agent.ps1',
            'content' => base64_encode($scriptContent),
            'size' => strlen($scriptContent),
        ]);
    }

    /**
     * Download wipe agent script
     * @PublicPage
     * @NoCSRFRequired
     */
    public function downloadWipeAgent(string $userId = ''): JSONResponse
    {
        $scriptPath = __DIR__ . '/../../tools/wipe-agent.ps1';
        if (!file_exists($scriptPath)) {
            return ApiResponse::error('Script not found', 404);
        }

        $scriptContent = file_get_contents($scriptPath);

        // Заменить плейсхолдеры на реальные значения
        if ($userId) {
            $user = $this->userSession->getUser();
            $currentUser = $user ? $user->getUID() : '';
            
            $scriptContent = str_replace(
                ['-Username ""', '-Password ""', '$Username = ""', '$Password = ""'],
                ['-Username "' . $userId . '"', '-Password "USER_PASSWORD"', '$Username = "' . $userId . '"', '$Password = "USER_PASSWORD"'],
                $scriptContent
            );
        }

        return ApiResponse::success([
            'filename' => 'wipe-agent-' . ($userId ?: 'generic') . '.ps1',
            'content' => base64_encode($scriptContent),
            'size' => strlen($scriptContent),
            'user_id' => $userId ?: null,
        ]);
    }

    /**
     * Check if wipe is requested for current user
     * @PublicPage
     * @NoCSRFRequired
     */
    public function checkWipeStatus(string $userId = ''): JSONResponse
    {
        try {
            // Если userId не передан — использовать текущего пользователя
            if (!$userId) {
                $user = $this->userSession->getUser();
                if (!$user) {
                    return ApiResponse::error('Not authenticated', 401);
                }
                $userId = $user->getUID();
            }

            // Проверяем через RemoteWipeService
            $wipeRequested = $this->remoteWipeService->hasPendingWipe($userId);

            return ApiResponse::success([
                'wipe_requested' => $wipeRequested,
                'user_id' => $userId,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
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

        // Check if user is admin
        // In Nextcloud, admin check is done via groups or app config
        // This is a simplified check
        $isAdmin = \OC::$server->get(\OCP\IGroupManager::class)->isAdmin($user->getUID());
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