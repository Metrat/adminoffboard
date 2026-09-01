<?php

declare(strict_types=1);

return [
    'routes' => [
        // Web routes for PageController
        [
            'name' => 'page#index',
            'url' => '/',
            'verb' => 'GET',
        ],
        [
            'name' => 'page#dashboard',
            'url' => '/dashboard',
            'verb' => 'GET',
        ],
        [
            'name' => 'page#offboard',
            'url' => '/offboard',
            'verb' => 'GET',
        ],
        [
            'name' => 'page#settings',
            'url' => '/settings',
            'verb' => 'GET',
        ],
        
        // API routes - version 1
        [
            'name' => 'Api#getDashboard',
            'url' => '/api/v1/dashboard',
            'verb' => 'GET',
        ],
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
    ],
];
