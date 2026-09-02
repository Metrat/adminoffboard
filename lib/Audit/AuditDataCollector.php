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

namespace OCA\AdminOffboard\Audit;

use OCA\AdminOffboard\Db\Repository\AuditLogRepository;

/**
 * Audit data collector for reports and statistics
 */
class AuditDataCollector
{
    public function __construct(
        private AuditLogRepository $repository,
        private AuditLogger $logger
    ) {
    }

    /**
     * Get audit statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStats();
    }

    /**
     * Get user activity report
     */
    public function getUserActivityReport(string $userId, int $days = 30): array
    {
        $cutoff = time() - ($days * 24 * 60 * 60);
        $logs = $this->repository->findByDateRange($cutoff, time(), 1000);
        
        // Filter by user
        $userLogs = array_filter($logs, function ($log) use ($userId) {
            return $log->getUserId() === $userId;
        });

        return [
            'user_id' => $userId,
            'period_days' => $days,
            'total_actions' => count($userLogs),
            'actions' => $this->groupByAction($userLogs),
            'status_breakdown' => $this->getStatusBreakdown($userLogs),
            'timeline' => $this->getTimeline($userLogs),
        ];
    }

    /**
     * Get administrator activity report
     */
    public function getAdminActivityReport(string $actor, int $days = 30): array
    {
        $cutoff = time() - ($days * 24 * 60 * 60);
        $logs = $this->repository->findByDateRange($cutoff, time(), 1000);
        
        // Filter by actor
        $actorLogs = array_filter($logs, function ($log) use ($actor) {
            return $log->getActor() === $actor;
        });

        return [
            'actor' => $actor,
            'period_days' => $days,
            'total_actions' => count($actorLogs),
            'actions' => $this->groupByAction($actorLogs),
            'targets' => $this->getTargetList($actorLogs),
            'timeline' => $this->getTimeline($actorLogs),
        ];
    }

    /**
     * Get action summary report
     */
    public function getActionSummaryReport(int $days = 30): array
    {
        $cutoff = time() - ($days * 24 * 60 * 60);
        $logs = $this->repository->findByDateRange($cutoff, time(), 10000);

        $summary = [
            'period_days' => $days,
            'total_actions' => count($logs),
            'by_action' => $this->groupByAction($logs),
            'by_status' => $this->getStatusBreakdown($logs),
            'top_actors' => $this->getTopActors($logs, 10),
            'top_targets' => $this->getTopTargets($logs, 10),
            'daily_trend' => $this->getDailyTrend($logs, $days),
        ];

        return $summary;
    }

    /**
     * Group logs by action
     */
    private function groupByAction(array $logs): array
    {
        $grouped = [];
        foreach ($logs as $log) {
            $action = $log->getAction();
            if (!isset($grouped[$action])) {
                $grouped[$action] = 0;
            }
            $grouped[$action]++;
        }
        return $grouped;
    }

    /**
     * Get status breakdown
     */
    private function getStatusBreakdown(array $logs): array
    {
        $breakdown = [
            AuditLogger::STATUS_SUCCESS => 0,
            AuditLogger::STATUS_FAILURE => 0,
            AuditLogger::STATUS_PARTIAL => 0,
        ];

        foreach ($logs as $log) {
            $status = $log->getStatus();
            if (isset($breakdown[$status])) {
                $breakdown[$status]++;
            }
        }

        return $breakdown;
    }

    /**
     * Get timeline data
     */
    private function getTimeline(array $logs): array
    {
        $timeline = [];
        foreach ($logs as $log) {
            $timestamp = $log->getTimestamp();
            $date = date('Y-m-d H:00:00', $timestamp);
            
            if (!isset($timeline[$date])) {
                $timeline[$date] = 0;
            }
            $timeline[$date]++;
        }

        ksort($timeline);
        return $timeline;
    }

    /**
     * Get list of targets
     */
    private function getTargetList(array $logs): array
    {
        $targets = [];
        foreach ($logs as $log) {
            $target = $log->getTarget();
            if ($target && !in_array($target, $targets)) {
                $targets[] = $target;
            }
        }
        return $targets;
    }

    /**
     * Get top actors
     */
    private function getTopActors(array $logs, int $limit = 10): array
    {
        $actors = [];
        foreach ($logs as $log) {
            $actor = $log->getActor();
            if (!isset($actors[$actor])) {
                $actors[$actor] = 0;
            }
            $actors[$actor]++;
        }

        arsort($actors);
        return array_slice($actors, 0, $limit, true);
    }

    /**
     * Get top targets
     */
    private function getTopTargets(array $logs, int $limit = 10): array
    {
        $targets = [];
        foreach ($logs as $log) {
            $target = $log->getTarget();
            if ($target) {
                if (!isset($targets[$target])) {
                    $targets[$target] = 0;
                }
                $targets[$target]++;
            }
        }

        arsort($targets);
        return array_slice($targets, 0, $limit, true);
    }

    /**
     * Get daily trend
     */
    private function getDailyTrend(array $logs, int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', time() - ($i * 24 * 60 * 60));
            $trend[$date] = 0;
        }

        foreach ($logs as $log) {
            $date = date('Y-m-d', $log->getTimestamp());
            if (isset($trend[$date])) {
                $trend[$date]++;
            }
        }

        return $trend;
    }
}