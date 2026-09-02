/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 */

/**
 * AdminOffboard API Client
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

    // Bulk operations
    bulkOperation(operation, data) {
        return this.request('/bulk', {
            method: 'POST',
            body: JSON.stringify({ operation, ...data })
        });
    }
}