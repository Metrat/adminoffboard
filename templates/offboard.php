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

<div id="adminoffboard-offboard">
    <div class="section-header">
        <h2>Offboard Users</h2>
        <p>Disable user accounts, delete all device tokens, and optionally perform remote wipe</p>
    </div>

    <div class="offboard-container">
        <!-- Single User Offboard -->
        <div class="offboard-section">
            <h3>Single User</h3>
            <div class="form-group">
                <label for="offboard-user-id">User ID</label>
                <div class="input-group">
                    <input type="text" id="offboard-user-id" placeholder="Enter user ID" class="input">
                    <button class="button button-secondary" id="offboard-user-lookup">Lookup</button>
                </div>
                <div id="offboard-user-info" class="user-info" style="display: none;"></div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="offboard-remote-wipe">
                    <span class="checkbox-text">Remote wipe all devices</span>
                    <span class="checkbox-hint">Sends wipe command to all connected devices</span>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="offboard-dry-run" checked>
                    <span class="checkbox-text">Dry run</span>
                    <span class="checkbox-hint">Preview changes without executing</span>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="offboard-queue">
                    <span class="checkbox-text">Queue for background processing</span>
                    <span class="checkbox-hint">Process in background, useful for large operations</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="button button-danger" id="offboard-execute">
                    <span class="icon icon-offboard"></span>
                    Offboard User
                </button>
                <button class="button button-secondary" id="offboard-reset">Reset</button>
            </div>

            <div id="offboard-result" class="result-container"></div>
        </div>

        <!-- Bulk Offboard -->
        <div class="offboard-section">
            <h3>Bulk Offboard</h3>
            <div class="form-group">
                <label for="offboard-user-list">User IDs (one per line)</label>
                <textarea id="offboard-user-list" rows="6" placeholder="user1&#10;user2&#10;user3" class="textarea"></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="offboard-bulk-remote-wipe">
                    <span class="checkbox-text">Remote wipe all devices</span>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="offboard-bulk-dry-run" checked>
                    <span class="checkbox-text">Dry run</span>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="offboard-bulk-queue">
                    <span class="checkbox-text">Queue for background processing</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="button button-danger" id="offboard-bulk-execute">
                    <span class="icon icon-bulk"></span>
                    Offboard All Users
                </button>
                <button class="button button-secondary" id="offboard-bulk-reset">Clear</button>
            </div>

            <div id="offboard-bulk-result" class="result-container"></div>
        </div>
    </div>
</div>

<style>
.offboard-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.offboard-section {
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 24px;
}

.offboard-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
}

.input-group {
    display: flex;
    gap: 10px;
}

.input-group .input {
    flex: 1;
}

.user-info {
    margin-top: 10px;
    padding: 10px;
    background: var(--color-background-dark);
    border-radius: 4px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.checkbox-text {
    font-weight: 500;
}

.checkbox-hint {
    display: block;
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 2px;
}

.result-container {
    margin-top: 20px;
    min-height: 50px;
}

@media (max-width: 1024px) {
    .offboard-container {
        grid-template-columns: 1fr;
    }
}
</style>