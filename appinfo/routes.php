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

return [
    'routes' => [
        // API routes - version 1
        [
            'name' => 'Api#getUsers',
            'url' => '/api/v1/users',
            'verb' => 'GET',
        ],
        [
            'name' => 'Api#offboardUser',
            'url' => '/api/v1/users/{userId}/offboard',
            'verb' => 'POST',
        ],
        [
            'name' => 'Api#disableUsers',
            'url' => '/api/v1/users/disable',
            'verb' => 'POST',
        ],
        [
            'name' => 'Api#deleteTokens',
            'url' => '/api/v1/users/tokens',
            'verb' => 'DELETE',
        ],
        [
            'name' => 'Api#remoteWipe',
            'url' => '/api/v1/users/{userId}/wipe',
            'verb' => 'POST',
        ],
        [
            'name' => 'Api#getAuditLogs',
            'url' => '/api/v1/audit',
            'verb' => 'GET',
        ],
        [
            'name' => 'Api#getJobStatus',
            'url' => '/api/v1/jobs/{jobId}',
            'verb' => 'GET',
        ],
        [
            'name' => 'Api#getQueueStats',
            'url' => '/api/v1/queue/stats',
            'verb' => 'GET',
        ],
        // Web UI routes
        [
            'name' => 'Page#index',
            'url' => '/',
            'verb' => 'GET',
        ],
        [
            'name' => 'Page#dashboard',
            'url' => '/dashboard',
            'verb' => 'GET',
        ],
        [
            'name' => 'Page#offboard',
            'url' => '/offboard',
            'verb' => 'GET',
        ],
        [
            'name' => 'Page#settings',
            'url' => '/settings',
            'verb' => 'GET',
        ],
    ],
];