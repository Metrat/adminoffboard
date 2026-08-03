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

?>

<div id="adminoffboard-settings">
    <div class="section-header">
        <h2>Settings</h2>
        <p>Configure Admin Offboard application settings</p>
    </div>

    <div class="settings-container">
        <!-- General Settings -->
        <div class="settings-section">
            <h3>General Settings</h3>
            
            <div class="form-group">
                <label for="setting-retention">Audit Log Retention (days)</label>
                <input type="number" id="setting-retention" value="90" min="1" max="365" class="input">
                <span class="input-hint">Number of days to keep audit logs before automatic cleanup</span>
            </div>

            <div class="form-group">
                <label for="setting-batch-size">Queue Batch Size</label>
                <input type="number" id="setting-batch-size" value="100" min="1" max="1000" class="input">
                <span class="input-hint">Number of jobs to process in a single batch</span>
            </div>

            <div class="form-group">
                <label for="setting-max-attempts">Max Job Attempts</label>
                <input type="number" id="setting-max-attempts" value="3" min="1" max="10" class="input">
                <span class="input-hint">Maximum number of retry attempts for failed jobs</span>
            </div>
        </div>

        <!-- Feature Toggles -->
        <div class="settings-section">
            <h3>Feature Toggles</h3>

            <div class="form-group toggle-group">
                <label class="toggle-label">
                    <input type="checkbox" id="setting-api" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-text">Enable API Access</span>
                </label>
                <span class="toggle-hint">Allow REST API access to Admin Offboard</span>
            </div>

            <div class="form-group toggle-group">
                <label class="toggle-label">
                    <input type="checkbox" id="setting-remote-wipe" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-text">Enable Remote Wipe</span>
                </label>
                <span class="toggle-hint">Allow remote wipe operations</span>
            </div>

            <div class="form-group toggle-group">
                <label class="toggle-label">
                    <input type="checkbox" id="setting-dry-run" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-text">Enable Dry Run Mode</span>
                </label>
                <span class="toggle-hint">Allow dry run mode for testing operations</span>
            </div>

            <div class="form-group toggle-group">
                <label class="toggle-label">
                    <input type="checkbox" id="setting-audit-log" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-text">Enable Audit Logging</span>
                </label>
                <span class="toggle-hint">Log all administrative actions</span>
            </div>
        </div>

        <!-- Advanced Settings -->
        <div class="settings-section">
            <h3>Advanced Settings</h3>

            <div class="form-group">
                <label for="setting-log-level">Log Level</label>
                <select id="setting-log-level" class="select">
                    <option value="debug">Debug</option>
                    <option value="info" selected>Info</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                    <option value="fatal">Fatal</option>
                </select>
            </div>

            <div class="form-group">
                <label for="setting-timeout">Operation Timeout (seconds)</label>
                <input type="number" id="setting-timeout" value="300" min="30" max="3600" class="input">
                <span class="input-hint">Maximum time allowed for a single operation</span>
            </div>

            <div class="form-group">
                <label for="setting-max-users">Max Users Per Operation</label>
                <input type="number" id="setting-max-users" value="1000" min="1" max="10000" class="input">
                <span class="input-hint">Maximum number of users per bulk operation</span>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="settings-section danger-zone">
            <h3>Danger Zone</h3>
            
            <div class="danger-actions">
                <div class="danger-action">
                    <div class="danger-info">
                        <strong>Cleanup Audit Logs</strong>
                        <p>Delete all audit logs older than the retention period</p>
                    </div>
                    <button class="button button-danger" id="cleanup-audit">
                        Cleanup Now
                    </button>
                </div>

                <div class="danger-action">
                    <div class="danger-info">
                        <strong>Reset All Settings</strong>
                        <p>Reset all settings to default values</p>
                    </div>
                    <button class="button button-danger" id="reset-settings">
                        Reset Settings
                    </button>
                </div>

                <div class="danger-action">
                    <div class="danger-info">
                        <strong>Clear Queue</strong>
                        <p>Delete all pending and failed jobs from the queue</p>
                    </div>
                    <button class="button button-danger" id="clear-queue">
                        Clear Queue
                    </button>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 30px;">
            <button class="button button-primary" id="settings-save">Save Settings</button>
            <button class="button button-secondary" id="settings-reload">Reload Defaults</button>
        </div>

        <div id="settings-result" class="result-container"></div>
    </div>
</div>

<style>
.settings-container {
    max-width: 800px;
    margin: 0 auto;
}

.settings-section {
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
}

.settings-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--color-border);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
}

.input, .select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    background: var(--color-main-background);
    color: var(--color-main-text);
}

.input-hint {
    display: block;
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 4px;
}

.toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.toggle-slider {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 24px;
    background: var(--color-border);
    border-radius: 12px;
    transition: all 0.3s;
    flex-shrink: 0;
}

.toggle-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    transition: all 0.3s;
}

.toggle-label input:checked + .toggle-slider {
    background: var(--color-primary);
}

.toggle-label input:checked + .toggle-slider::after {
    left: 18px;
}

.toggle-label input {
    display: none;
}

.toggle-text {
    font-weight: 500;
}

.toggle-hint {
    display: block;
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 2px;
}

.danger-zone {
    border-color: var(--color-error);
    border-width: 2px;
}

.danger-zone h3 {
    color: var(--color-error);
    border-bottom-color: var(--color-error);
}

.danger-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.danger-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--color-background-dark);
    border-radius: 4px;
}

.danger-info strong {
    display: block;
}

.danger-info p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: var(--color-text-lighter);
}

.result-container {
    margin-top: 20px;
    min-height: 50px;
}
</style>