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

(function() {
    'use strict';

    /**
     * API Client for Admin Offboard
     */
    class AdminOffboardAPI {
        constructor() {
            this.baseUrl = OC.generateUrl('/apps/adminoffboard/api/v1');
        }

        async request(endpoint, options = {}) {
            const url = `${this.baseUrl}${endpoint}`;
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'requesttoken': OC.requestToken
                },
                ...options
            };

            try {
                const response = await fetch(url, config);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'API request failed');
                }
                return data.data || data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // User endpoints
        getUserStats() {
            return this.request('/users/stats');
        }

        getUsers(search = '', limit = 50, offset = 0) {
            const params = new URLSearchParams({ search, limit, offset });
            return this.request(`/users?${params}`);
        }

        // Queue endpoints
        getQueueStats() {
            return this.request('/queue/stats');
        }

        getJobStatus(jobId) {
            return this.request(`/jobs/${jobId}`);
        }

        cancelJob(jobId) {
            return this.request(`/jobs/${jobId}/cancel`, { method: 'POST' });
        }

        // Audit endpoints
        getAuditLogs(params = {}) {
            const query = new URLSearchParams(params).toString();
            return this.request(`/audit?${query}`);
        }

        getAuditStats() {
            return this.request('/audit/stats');
        }

        searchAudit(search, limit = 100) {
            return this.request(`/audit/search?search=${encodeURIComponent(search)}&limit=${limit}`);
        }

        // Offboard endpoints
        offboardUser(userId, remoteWipe = false, dryRun = false, queue = false) {
            return this.request(`/users/${userId}/offboard`, {
                method: 'POST',
                body: JSON.stringify({ remote_wipe: remoteWipe, dry_run: dryRun, queue })
            });
        }

        offboardUsers(userIds, remoteWipe = false, dryRun = false, queue = false) {
            return this.request('/users/offboard', {
                method: 'POST',
                body: JSON.stringify({ user_ids: userIds, remote_wipe: remoteWipe, dry_run: dryRun, queue })
            });
        }

        // Disable endpoints
        disableUsers(userIds, dryRun = false, queue = false) {
            return this.request('/users/disable', {
                method: 'POST',
                body: JSON.stringify({ user_ids: userIds, dry_run: dryRun, queue })
            });
        }

        // Token endpoints
        deleteTokens(userIds, dryRun = false, queue = false) {
            return this.request('/users/tokens', {
                method: 'DELETE',
                body: JSON.stringify({ user_ids: userIds, dry_run: dryRun, queue })
            });
        }

        // Remote wipe endpoints
        remoteWipe(userId, deviceId = null, dryRun = false, queue = false) {
            return this.request(`/users/${userId}/wipe`, {
                method: 'POST',
                body: JSON.stringify({ device_id: deviceId, dry_run: dryRun, queue })
            });
        }

        canWipe(userId) {
            return this.request(`/users/${userId}/can-wipe`);
        }
    }

    /**
     * Vue Components
     */
    const Dashboard = {
        template: `
            <div>
                <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2>Dashboard</h2>
                    <button class="button" @click="refresh" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Refresh' }}
                    </button>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-users"></div>
                        <div class="stat-content">
                            <span class="stat-label">Total Users</span>
                            <span class="stat-value">{{ stats.total || '-' }}</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-active"></div>
                        <div class="stat-content">
                            <span class="stat-label">Active Users</span>
                            <span class="stat-value">{{ stats.enabled || '-' }}</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-disabled"></div>
                        <div class="stat-content">
                            <span class="stat-label">Disabled Users</span>
                            <span class="stat-value">{{ stats.disabled || '-' }}</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-devices"></div>
                        <div class="stat-content">
                            <span class="stat-label">Queue Jobs</span>
                            <span class="stat-value">{{ queueStats.total || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="queue-stats" style="background:var(--color-main-background); border:1px solid var(--color-border); border-radius:8px; padding:20px; margin-bottom:30px;">
                    <h3 style="margin:0 0 15px 0; font-size:16px;">Queue Status</h3>
                    <div class="queue-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                        <div class="queue-item" style="text-align:center; padding:10px; background:var(--color-background-dark); border-radius:6px;">
                            <span class="queue-label" style="display:block; font-size:12px; color:var(--color-text-lighter);">Pending</span>
                            <span class="queue-value" style="display:block; font-size:20px; font-weight:600;">{{ queueStats.pending || 0 }}</span>
                        </div>
                        <div class="queue-item" style="text-align:center; padding:10px; background:var(--color-background-dark); border-radius:6px;">
                            <span class="queue-label" style="display:block; font-size:12px; color:var(--color-text-lighter);">Processing</span>
                            <span class="queue-value" style="display:block; font-size:20px; font-weight:600;">{{ queueStats.processing || 0 }}</span>
                        </div>
                        <div class="queue-item" style="text-align:center; padding:10px; background:var(--color-background-dark); border-radius:6px;">
                            <span class="queue-label" style="display:block; font-size:12px; color:var(--color-text-lighter);">Completed</span>
                            <span class="queue-value" style="display:block; font-size:20px; font-weight:600;">{{ queueStats.completed || 0 }}</span>
                        </div>
                        <div class="queue-item" style="text-align:center; padding:10px; background:var(--color-background-dark); border-radius:6px;">
                            <span class="queue-label" style="display:block; font-size:12px; color:var(--color-text-lighter);">Failed</span>
                            <span class="queue-value" style="display:block; font-size:20px; font-weight:600;">{{ queueStats.failed || 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="recent-activity" style="background:var(--color-main-background); border:1px solid var(--color-border); border-radius:8px; padding:20px;">
                    <h3 style="margin:0 0 15px 0; font-size:16px;">Recent Activity</h3>
                    <div class="activity-list" style="min-height:100px;">
                        <div v-if="recentLogs.length === 0" class="activity-empty" style="color:var(--color-text-lighter); text-align:center; padding:30px 0;">
                            No recent activity
                        </div>
                        <div v-for="log in recentLogs" :key="log.id" class="activity-item" style="display:flex; align-items:center; padding:10px 0; border-bottom:1px solid var(--color-border);">
                            <div class="activity-icon" :class="log.status" style="width:32px; height:32px; border-radius:50%; margin-right:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:var(--color-primary); color:#fff;">
                                {{ log.action.charAt(0).toUpperCase() }}
                            </div>
                            <div class="activity-content" style="flex:1;">
                                <div class="activity-title" style="font-weight:500;">{{ log.action }}</div>
                                <div class="activity-time" style="font-size:12px; color:var(--color-text-lighter);">{{ formatDate(log.timestamp) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                stats: {},
                queueStats: {},
                recentLogs: [],
                loading: false
            };
        },
        mounted() {
            this.refresh();
        },
        methods: {
            async refresh() {
                this.loading = true;
                try {
                    const [stats, queueStats, logs] = await Promise.all([
                        this.$api.getUserStats(),
                        this.$api.getQueueStats(),
                        this.$api.getAuditLogs({ limit: 10 })
                    ]);
                    this.stats = stats;
                    this.queueStats = queueStats;
                    this.recentLogs = logs.items || [];
                } catch (error) {
                    console.error('Failed to load dashboard:', error);
                } finally {
                    this.loading = false;
                }
            },
            formatDate(timestamp) {
                return new Date(timestamp * 1000).toLocaleString();
            }
        }
    };

    const Offboard = {
        template: `
            <div>
                <h2>Offboard User</h2>
                <p style="color:var(--color-text-lighter); margin-bottom:20px;">Disable user account, delete all device tokens, and optionally perform remote wipe</p>
                <div class="adminoffboard-form" style="max-width:600px; margin:0 auto;">
                    <div class="form-group" style="margin-bottom:20px;">
                        <label for="offboard-user" style="display:block; font-weight:500; margin-bottom:5px;">User ID</label>
                        <input type="text" id="offboard-user" v-model="userId" placeholder="Enter user ID" style="width:100%; padding:10px; border:1px solid var(--color-border); border-radius:4px; background:var(--color-main-background); color:var(--color-main-text);">
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" v-model="remoteWipe">
                            <span>Remote wipe devices</span>
                            <span style="font-size:12px; color:var(--color-text-lighter);">Sends wipe command to all connected devices</span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" v-model="dryRun" checked>
                            <span>Dry run (preview only)</span>
                            <span style="font-size:12px; color:var(--color-text-lighter);">Preview changes without executing</span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" v-model="queue">
                            <span>Queue for background processing</span>
                            <span style="font-size:12px; color:var(--color-text-lighter);">Process in background, useful for large operations</span>
                        </label>
                    </div>
                    <div class="form-actions" style="display:flex; gap:10px; margin-top:20px;">
                        <button class="button button-danger" @click="execute" :disabled="!userId || loading" style="padding:8px 16px; border:none; border-radius:4px; cursor:pointer; font-weight:500; background:var(--color-error); color:#fff;">
                            {{ loading ? 'Processing...' : 'Offboard User' }}
                        </button>
                        <button class="button button-secondary" @click="reset" style="padding:8px 16px; border:none; border-radius:4px; cursor:pointer; font-weight:500; background:var(--color-background-dark); color:var(--color-main-text);">Reset</button>
                    </div>
                    <div v-if="result" style="margin-top:20px;">
                        <div class="alert" :class="resultType" style="padding:12px 16px; border-radius:4px; margin-bottom:15px;">
                            <pre style="margin:0; white-space:pre-wrap; word-wrap:break-word;">{{ result }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                userId: '',
                remoteWipe: false,
                dryRun: true,
                queue: false,
                loading: false,
                result: null,
                resultType: ''
            };
        },
        methods: {
            async execute() {
                if (!this.userId) return;
                this.loading = true;
                this.result = null;
                try {
                    const data = await this.$api.offboardUser(
                        this.userId,
                        this.remoteWipe,
                        this.dryRun,
                        this.queue
                    );
                    this.result = JSON.stringify(data, null, 2);
                    this.resultType = 'alert-success';
                } catch (error) {
                    this.result = `Error: ${error.message}`;
                    this.resultType = 'alert-error';
                } finally {
                    this.loading = false;
                }
            },
            reset() {
                this.userId = '';
                this.remoteWipe = false;
                this.dryRun = true;
                this.queue = false;
                this.result = null;
                this.resultType = '';
            }
        }
    };

    /**
     * Vue Router
     */
    const routes = [
        { path: '/', redirect: '/dashboard' },
        { path: '/dashboard', component: Dashboard, name: 'dashboard' },
        { path: '/offboard', component: Offboard, name: 'offboard' },
        { path: '/bulk', component: { template: '<div><h2>Bulk Operations</h2><p>Bulk operations component</p></div>' }, name: 'bulk' },
        { path: '/audit', component: { template: '<div><h2>Audit Logs</h2><p>Audit logs component</p></div>' }, name: 'audit' },
        { path: '/queue', component: { template: '<div><h2>Queue Management</h2><p>Queue management component</p></div>' }, name: 'queue' },
        { path: '/settings', component: { template: '<div><h2>Settings</h2><p>Settings component</p></div>' }, name: 'settings' }
    ];

    const router = new VueRouter({
        routes,
        mode: 'hash'
    });

    /**
     * Main App
     */
    const app = new Vue({
        router,
        el: '#adminoffboard-app',
        data: {
            api: null
        },
        created() {
            this.api = new AdminOffboardAPI();
            Vue.prototype.$api = this.api;
        },
        mounted() {
            this.updateNav(this.$route.name);
            this.$router.afterEach((to) => {
                this.updateNav(to.name);
            });
        },
        methods: {
            updateNav(routeName) {
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.toggle('active', item.dataset.view === routeName);
                });
            }
        },
        template: `
            <div class="adminoffboard-container" style="display:flex; height:100vh; background:var(--color-main-background); color:var(--color-main-text);">
                <nav class="adminoffboard-nav" style="width:280px; min-height:100vh; background:var(--color-primary); color:var(--color-primary-text); padding:20px 0; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto;">
                    <div class="nav-header" style="padding:0 20px 20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; gap:10px;">
                        <img src="<?php p(image_path('adminoffboard', 'logo.svg')); ?>" alt="Admin Offboard" class="nav-logo" style="width:40px; height:40px;">
                        <h1 style="font-size:18px; font-weight:600; margin:0;">Admin Offboard</h1>
                        <span class="version" style="font-size:11px; opacity:0.7; margin-left:auto;">v<?php p($_['version'] ?? '0.1.0'); ?></span>
                    </div>
                    <ul class="nav-menu" style="list-style:none; padding:20px 0; margin:0;">
                        <li class="nav-item active" data-view="dashboard" style="margin:2px 10px;">
                            <a @click.prevent="$router.push('/dashboard')" href="#dashboard" style="display:flex; align-items:center; padding:10px 15px; border-radius:6px; color:rgba(255,255,255,0.8); text-decoration:none; transition:all 0.2s; cursor:pointer;">
                                <span class="icon icon-dashboard" style="width:20px; height:20px; margin-right:12px; display:inline-block; background-size:contain; background-repeat:no-repeat; background-position:center; filter:invert(1);"></span>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item" data-view="offboard" style="margin:2px 10px;">
                            <a @click.prevent="$router.push('/offboard')" href="#offboard" style="display:flex; align-items:center; padding:10px 15px; border-radius:6px; color:rgba(255,255,255,0.8); text-decoration:none; transition:all 0.2s; cursor:pointer;">
                                <span class="icon icon-user-offboard" style="width:20px; height:20px; margin-right:12px; display:inline-block; background-size:contain; background-repeat:no-repeat; background-position:center; filter:invert(1);"></span>
                                Offboard Users
                            </a>
                        </li>
                        <li class="nav-item" data-view="bulk" style="margin:2px 10px;">
                            <a @click.prevent="$router.push('/bulk')" href="#bulk" style="display:flex; align-items:center; padding:10px 15px; border-radius:6px; color:rgba(255,255,255,0.8); text-decoration:none; transition:all 0.2s; cursor:pointer;">
                                <span class="icon icon-bulk" style="width:20px; height:20px; margin-right:12px; display:inline-block; background-size:contain; background-repeat:no-repeat; background-position:center; filter:invert(1);"></span>
                                Bulk Operations
                            </a>
                        </li>
                        <li class="nav-item" data-view="audit" style="margin:2px 10px;">
                            <a @click.prevent="$router.push('/audit')" href="#audit" style="display:flex; align-items:center; padding:10px 15px; border-radius:6px; color:rgba(255,255,255,0.8); text-decoration:none; transition:all 0.2s; cursor:pointer;">
                                <span class="icon icon-audit" style="width:20px; height:20px; margin-right:12px; display:inline-block; background-size:contain; background-repeat:no-repeat; background-position:center; filter:invert(1);"></span>
                                Audit Logs
                            </a>
                        </li>
                        <li class="nav-item" data-view="queue" style="margin:2px 10px;">
                            <a @click.prevent="$router.push('/queue')" href="#queue" style="display:flex; align-items:center; padding:10px 15px; border-radius:6px; color:rgba(255,255,255,0.8); text-decoration:none; transition:all 0.2s; cursor:pointer;">
                                <span class="icon icon-queue" style="width:20px; height:20px; margin-right:12px; display:inline-block; background-size:contain; background-repeat:no-repeat; background-position:center; filter:invert(1);"></span>
                                Queue
                            </a>
                        </li>
                        <li class="nav-item" data-view="settings" style="margin:2px 10px;">
                            <a @click.prevent="$router.push('/settings')" href="#settings" style="display:flex; align-items:center; padding:10px 15px; border-radius:6px; color:rgba(255,255,255,0.8); text-decoration:none; transition:all 0.2s; cursor:pointer;">
                                <span class="icon icon-settings" style="width:20px; height:20px; margin-right:12px; display:inline-block; background-size:contain; background-repeat:no-repeat; background-position:center; filter:invert(1);"></span>
                                Settings
                            </a>
                        </li>
                    </ul>
                </nav>

                <main class="adminoffboard-content" style="flex:1; padding:30px; overflow-y:auto; background:var(--color-main-background);">
                    <router-view></router-view>
                </main>
            </div>
        `
    });

})();