<?php

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

script('adminoffboard', 'adminoffboard-dashboard');
style('adminoffboard', 'adminoffboard');

?>

<div id="adminoffboard-dashboard">
    <div class="dashboard-header">
        <h2>Dashboard</h2>
        <div class="dashboard-actions">
            <button class="button" id="refresh-stats">
                <span class="icon icon-refresh"></span>
                Refresh
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-users"></div>
            <div class="stat-content">
                <span class="stat-label">Total Users</span>
                <span class="stat-value" id="total-users">-</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-active"></div>
            <div class="stat-content">
                <span class="stat-label">Active Users</span>
                <span class="stat-value" id="active-users">-</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-disabled"></div>
            <div class="stat-content">
                <span class="stat-label">Disabled Users</span>
                <span class="stat-value" id="disabled-users">-</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-devices"></div>
            <div class="stat-content">
                <span class="stat-label">Total Devices</span>
                <span class="stat-value" id="total-devices">-</span>
            </div>
        </div>
    </div>

    <!-- Queue Stats -->
    <div class="queue-stats">
        <h3>Queue Status</h3>
        <div class="queue-grid">
            <div class="queue-item">
                <span class="queue-label">Pending</span>
                <span class="queue-value" id="queue-pending">0</span>
            </div>
            <div class="queue-item">
                <span class="queue-label">Processing</span>
                <span class="queue-value" id="queue-processing">0</span>
            </div>
            <div class="queue-item">
                <span class="queue-label">Completed</span>
                <span class="queue-value" id="queue-completed">0</span>
            </div>
            <div class="queue-item">
                <span class="queue-label">Failed</span>
                <span class="queue-value" id="queue-failed">0</span>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <div class="activity-list" id="recent-activity">
            <div class="activity-empty">No recent activity</div>
        </div>
    </div>
</div>