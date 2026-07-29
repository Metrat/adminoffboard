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

script('adminoffboard', 'adminoffboard');
style('adminoffboard', 'adminoffboard');

?>

<div id="adminoffboard-app">
    <div class="adminoffboard-container">
        <!-- Navigation -->
        <nav class="adminoffboard-nav">
            <div class="nav-header">
                <img src="<?php p(image_path('adminoffboard', 'logo.svg')); ?>" alt="Admin Offboard" class="nav-logo">
                <h1>Admin Offboard</h1>
                <span class="version">v<?php p($_['version'] ?? '0.1.0'); ?></span>
            </div>
            <ul class="nav-menu">
                <li class="nav-item active" data-view="dashboard">
                    <a href="#dashboard">
                        <span class="icon icon-dashboard"></span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item" data-view="offboard">
                    <a href="#offboard">
                        <span class="icon icon-user-offboard"></span>
                        Offboard Users
                    </a>
                </li>
                <li class="nav-item" data-view="bulk">
                    <a href="#bulk">
                        <span class="icon icon-bulk"></span>
                        Bulk Operations
                    </a>
                </li>
                <li class="nav-item" data-view="audit">
                    <a href="#audit">
                        <span class="icon icon-audit"></span>
                        Audit Logs
                    </a>
                </li>
                <li class="nav-item" data-view="queue">
                    <a href="#queue">
                        <span class="icon icon-queue"></span>
                        Queue
                    </a>
                </li>
                <li class="nav-item" data-view="settings">
                    <a href="#settings">
                        <span class="icon icon-settings"></span>
                        Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="adminoffboard-content">
            <div id="app-content">
                <!-- Content will be rendered by JavaScript -->
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Loading Admin Offboard...</p>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Templates for JavaScript -->
<script type="text/template" id="template-dashboard">
    <!-- Dashboard content -->
</script>

<script type="text/template" id="template-offboard">
    <!-- Offboard content -->
</script>

<script type="text/template" id="template-bulk">
    <!-- Bulk operations content -->
</script>

<script type="text/template" id="template-audit">
    <!-- Audit logs content -->
</script>

<script type="text/template" id="template-queue">
    <!-- Queue content -->
</script>

<script type="text/template" id="template-settings">
    <!-- Settings content -->
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue-router@3.6.5/dist/vue-router.min.js"></script>
<script src="<?php p(script('adminoffboard', 'adminoffboard')); ?>"></script>