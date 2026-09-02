(function() {
    'use strict';

    console.log('AdminOffboard: Starting');

    window.apiDelete = function(endpoint, data) {
        var url = OC.generateUrl('/apps/adminoffboard/api/v1' + endpoint);
        return fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(data || {})
        }).then(function(response) {
            return response.json();
        });
    }

    window.apiPost = function(endpoint, data) {
        var url = OC.generateUrl('/apps/adminoffboard/api/v1' + endpoint);
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(data)
        }).then(function(response) {
            return response.json();
        });
    }

    window.apiRequest = function(endpoint) {
        var url = OC.generateUrl('/apps/adminoffboard/api/v1' + endpoint);
        return fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'requesttoken': OC.requestToken
            }
        }).then(function(response) {
            return response.json();
        });
    }

    function renderDashboard() {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (!mountPoint) {
            console.error('adminoffboard-content not found');
            return;
        }

        mountPoint.innerHTML = '<div style="padding:20px; width:100%; max-width:100%; box-sizing:border-box;"><h2>Dashboard</h2><p>Loading...</p></div>';

        apiRequest('/dashboard').then(function(response) {
            if (response.success) {
                var data = response.data;
                var html = '<div style="padding:20px; width:100%; max-width:100%; box-sizing:border-box;">';
                html += '<h2 style="color:#333; margin-bottom:20px;">Dashboard</h2>';
                
                // Stats cards
                html += '<div style="display:flex; gap:20px; margin-bottom:30px; flex-wrap:wrap;">';
                html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; flex:1; min-width:200px; border:1px solid #ddd;">';
                html += '<h3 style="color:#333; margin:0 0 10px 0; font-size:14px;">Total Users</h3>';
                html += '<div style="font-size:32px; font-weight:bold; color:#333;">' + data.users.total + '</div>';
                html += '<div style="color:#4caf50; font-size:14px;">' + data.users.enabled + ' enabled</div>';
                html += '<div style="color:#f44336; font-size:14px;">' + data.users.disabled + ' disabled</div>';
                html += '</div>';
                
                html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; flex:1; min-width:200px; border:1px solid #ddd;">';
                html += '<h3 style="color:#333; margin:0 0 10px 0; font-size:14px;">Audit Logs</h3>';
                html += '<div style="font-size:32px; font-weight:bold; color:#333;">' + data.audit.total + '</div>';
                html += '</div>';
                
                html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; flex:1; min-width:200px; border:1px solid #ddd;">';
                html += '<h3 style="color:#333; margin:0 0 10px 0; font-size:14px;">Queue</h3>';
                html += '<div style="font-size:32px; font-weight:bold; color:#333;">' + data.queue.pending + '</div>';
                html += '<div style="color:#ff9800; font-size:14px;">' + data.queue.processing + ' processing</div>';
                html += '</div>';
                html += '</div>';
                
                // Recent Activity
                html += '<h3 style="color:#333; margin-bottom:15px;">Recent Activity</h3>';
                if (data.recentActivity && data.recentActivity.length > 0) {
                    for (var i = 0; i < data.recentActivity.length; i++) {
                        var activity = data.recentActivity[i];
                        html += '<div style="background:#f5f5f5; border-radius:6px; padding:15px; margin-bottom:10px; border:1px solid #ddd;">';
                        html += '<div><strong>' + activity.action + '</strong> - ' + activity.userId + '</div>';
                        html += '<div style="color:#888; font-size:12px;">' + new Date(activity.timestamp * 1000).toLocaleString() + '</div>';
                        html += '</div>';
                    }
                } else {
                    html += '<p style="color:#666;">No recent activity</p>';
                }
                
                html += '</div>';
                mountPoint.innerHTML = html;
            }
        }).catch(function(error) {
            mountPoint.innerHTML = '<div style="padding:20px; width:100%; max-width:100%; box-sizing:border-box; color:red;">Error loading dashboard</div>';
        });
    }

    window.disableUser = function(userId) {
        if (confirm('Disable user ' + userId + '?')) {
            apiPost('/users/disable', { user_ids: [userId] }).then(function(response) {
                if (response.success) {
                    alert('User ' + userId + ' disabled successfully');
                    renderOffboardUsers();
                } else {
                    alert('Error: ' + response.message);
                }
            }).catch(function(error) {
                alert('Error disabling user: ' + error.message);
            });
        }
    };
    
    window.remoteWipeUser = function(userId) {
        if (confirm('Remote wipe user ' + userId + '? This will delete all tokens and send wipe signal.')) {
            apiPost('/users/' + userId + '/wipe', {}).then(function(response) {
                if (response.success) {
                    alert('Remote wipe initiated for ' + userId);
                    renderOffboardUsers();
                } else {
                    alert('Error: ' + response.message);
                }
            }).catch(function(error) {
                alert('Error remote wiping: ' + error.message);
            });
        }
    };
    
    window.bulkDisableUsers = function() {
        var users = document.getElementById('bulk-users').value.split('\n').filter(function(u) { return u.trim(); });
        if (users.length === 0) {
            alert('Please enter at least one user ID');
            return;
        }
        if (confirm('Disable ' + users.length + ' users?')) {
            apiPost('/users/disable', { user_ids: users }).then(function(response) {
                if (response.success) {
                    alert('Disabled ' + users.length + ' users');
                    document.getElementById('bulk-users').value = '';
                } else {
                    alert('Error: ' + response.message);
                }
            }).catch(function(error) {
                alert('Error: ' + error.message);
            });
        }
    };
    
    window.bulkDeleteTokens = function() {
        var users = document.getElementById('bulk-token-users').value.split('\n').filter(function(u) { return u.trim(); });
        if (users.length === 0) {
            alert('Please enter at least one user ID');
            return;
        }
        if (confirm('Delete tokens for ' + users.length + ' users?')) {
            apiPost('/users/tokens', { user_ids: users }).then(function(response) {
                if (response.success) {
                    alert('Deleted tokens for ' + users.length + ' users');
                    document.getElementById('bulk-token-users').value = '';
                } else {
                    alert('Error: ' + response.message);
                }
            }).catch(function(error) {
                alert('Error: ' + error.message);
            });
        }
    };
    
    window.bulkRemoteWipe = function() {
        var users = document.getElementById('bulk-wipe-users').value.split('\n').filter(function(u) { return u.trim(); });
        if (users.length === 0) {
            alert('Please enter at least one user ID');
            return;
        }
        if (confirm('Remote wipe ' + users.length + ' users? This will delete all tokens.')) {
            var promises = [];
            for (var i = 0; i < users.length; i++) {
                promises.push(apiPost('/users/' + users[i] + '/wipe', {}));
            }
            Promise.all(promises).then(function(results) {
                var successCount = 0;
                for (var i = 0; i < results.length; i++) {
                    if (results[i].success) successCount++;
                }
                alert('Remote wipe completed for ' + successCount + '/' + users.length + ' users');
                document.getElementById('bulk-wipe-users').value = '';
            }).catch(function(error) {
                alert('Error: ' + error.message);
            });
        }
    };

    window.processQueue = function() {
        apiPost('/queue/process', {}).then(function(response) {
            if (response.success) {
                alert('Queue processed');
                renderQueue();
            } else {
                alert('Error: ' + response.message);
            }
        }).catch(function(error) {
            alert('Error: ' + error.message);
        });
    };
    
    window.processAllQueue = function() {
        apiPost('/queue/process-all', {}).then(function(response) {
            if (response.success) {
                alert('All jobs processed');
                renderQueue();
            } else {
                alert('Error: ' + response.message);
            }
        }).catch(function(error) {
            alert('Error: ' + error.message);
        });
    };

    window.deleteTokens = function(userId) {
        if (confirm('Delete all tokens for user ' + userId + '?')) {
            apiPost('/users/tokens', { user_ids: [userId] }).then(function(response) {
                if (response.success) {
                    alert('Tokens deleted for ' + userId);
                    renderOffboardUsers();
                } else {
                    alert('Error: ' + response.message);
                }
            }).catch(function(error) {
                alert('Error deleting tokens: ' + error.message);
            });
        }
    };

    function fixWidth() {
        var content = document.getElementById('adminoffboard-content');
        if (!content) return;
        
        // Принудительно установить ширину
        var appWidth = document.getElementById('adminoffboard-app').offsetWidth;
        var navWidth = 280;
        var availableWidth = appWidth - navWidth;
        
        content.style.width = availableWidth + 'px';
        content.style.minWidth = availableWidth + 'px';
        content.style.maxWidth = '100%';
        content.style.flex = '1 1 auto';
        
        // Внутренние элементы
        var children = content.children;
        for (var i = 0; i < children.length; i++) {
            children[i].style.width = '100%';
            children[i].style.maxWidth = '100%';
            children[i].style.boxSizing = 'border-box';
        }
    }

    function renderSettings() {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (!mountPoint) return;

        var html = '<div style="padding:20px; width:100%; box-sizing:border-box;">';
        html += '<h2 style="color:#333; margin-bottom:20px;">Settings</h2>';
        
        html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd; margin-bottom:20px;">';
        html += '<h3 style="color:#333; margin:0 0 15px 0;">Application Info</h3>';
        html += '<p style="color:#666; margin:5px 0;">Version: <strong>0.2.6</strong></p>';
        html += '<p style="color:#666; margin:5px 0;">App ID: <strong>adminoffboard</strong></p>';
        html += '<p style="color:#666; margin:5px 0;">Namespace: <strong>AdminOffboard</strong></p>';
        html += '</div>';
        
        html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd; margin-bottom:20px;">';
        html += '<h3 style="color:#333; margin:0 0 15px 0;">Background Job</h3>';
        html += '<p style="color:#666; margin:5px 0;">Queue processing runs every <strong>5 minutes</strong> automatically.</p>';
        html += '<p style="color:#666; margin:5px 0;">Use the Queue page to process jobs manually.</p>';
        html += '</div>';
        
        html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd;">';
        html += '<h3 style="color:#333; margin:0 0 15px 0;">Console Commands</h3>';
        html += '<div style="background:#1a1a1a; border-radius:4px; padding:15px; color:#4caf50; font-family:monospace; font-size:13px; line-height:1.6;">';
        html += 'occ adminoffboard:test<br>';
        html += 'occ adminoffboard:users:disable<br>';
        html += 'occ adminoffboard:tokens:delete<br>';
        html += 'occ adminoffboard:offboard<br>';
        html += 'occ adminoffboard:process-queue<br>';
        html += 'occ adminoffboard:remote-wipe';
        html += '</div>';
        html += '</div>';
        
        html += '</div>';
        
        mountPoint.innerHTML = html;
    }

    function renderBulk() {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (!mountPoint) return;

        var html = '<div style="padding:20px; width:100%; box-sizing:border-box;">';
        html += '<h2 style="color:#333; margin-bottom:20px;">Bulk Operations</h2>';
        
        html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd; margin-bottom:20px;">';
        html += '<h3 style="color:#333; margin:0 0 15px 0;">Disable Users</h3>';
        html += '<textarea id="bulk-users" placeholder="Enter user IDs, one per line" style="width:100%; min-height:100px; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;"></textarea>';
        html += '<button onclick="bulkDisableUsers()" style="margin-top:10px; padding:10px 20px; background:#f44336; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">Disable Users</button>';
        html += '</div>';
        
        html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd; margin-bottom:20px;">';
        html += '<h3 style="color:#333; margin:0 0 15px 0;">Delete Tokens</h3>';
        html += '<textarea id="bulk-token-users" placeholder="Enter user IDs, one per line" style="width:100%; min-height:100px; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;"></textarea>';
        html += '<button onclick="bulkDeleteTokens()" style="margin-top:10px; padding:10px 20px; background:#2196f3; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">Delete Tokens</button>';
        html += '</div>';
        
        html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd;">';
        html += '<h3 style="color:#333; margin:0 0 15px 0;">Remote Wipe</h3>';
        html += '<textarea id="bulk-wipe-users" placeholder="Enter user IDs, one per line" style="width:100%; min-height:100px; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;"></textarea>';
        html += '<button onclick="bulkRemoteWipe()" style="margin-top:10px; padding:10px 20px; background:#ff9800; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">Remote Wipe</button>';
        html += '</div>';
        
        html += '</div>';
        
        mountPoint.innerHTML = html;
    }

    function renderQueue() {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (!mountPoint) return;

        mountPoint.innerHTML = '<div style="padding:20px; width:100%; box-sizing:border-box;"><h2 style="color:#333;">Queue Management</h2><p>Loading queue stats...</p></div>';

        apiRequest('/queue/stats').then(function(response) {
            if (response.success) {
                var stats = response.data;
                var html = '<div style="padding:20px; width:100%; box-sizing:border-box;">';
                html += '<h2 style="color:#333; margin-bottom:20px;">Queue Management</h2>';
                
                // Stats cards
                html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom:30px;">';
                
                var statCards = [
                    { label: 'Pending', value: stats.pending, color: '#ff9800' },
                    { label: 'Processing', value: stats.processing, color: '#2196f3' },
                    { label: 'Completed', value: stats.completed, color: '#4caf50' },
                    { label: 'Failed', value: stats.failed, color: '#f44336' },
                    { label: 'Cancelled', value: stats.cancelled, color: '#9e9e9e' }
                ];
                
                for (var i = 0; i < statCards.length; i++) {
                    var card = statCards[i];
                    html += '<div style="background:#f5f5f5; border-radius:8px; padding:20px; border:1px solid #ddd; text-align:center;">';
                    html += '<div style="font-size:12px; color:#888; margin-bottom:8px;">' + card.label + '</div>';
                    html += '<div style="font-size:36px; font-weight:bold; color:' + card.color + ';">' + card.value + '</div>';
                    html += '</div>';
                }
                html += '</div>';
                
                // Actions
                html += '<div style="display:flex; gap:10px; margin-bottom:30px;">';
                html += '<button onclick="processQueue()" style="padding:10px 20px; background:#4caf50; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">Process Next Job</button>';
                html += '<button onclick="processAllQueue()" style="padding:10px 20px; background:#2196f3; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">Process All</button>';
                html += '</div>';
                
                html += '<p style="color:#888; font-size:14px;">Background job runs every 5 minutes automatically.</p>';
                html += '</div>';
                
                mountPoint.innerHTML = html;
            }
        }).catch(function(error) {
            mountPoint.innerHTML = '<div style="padding:20px; color:red;">Error loading queue stats</div>';
        });
    }

    function renderAuditLogs() {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (!mountPoint) return;

        mountPoint.innerHTML = '<div style="padding:20px; width:100%; box-sizing:border-box;"><h2 style="color:#333;">Audit Logs</h2><p>Loading audit logs...</p></div>';

        apiRequest('/audit').then(function(response) {
            if (response.success) {
                var logs = response.data.logs;
                var html = '<div style="padding:20px; width:100%; box-sizing:border-box;">';
                html += '<h2 style="color:#333; margin-bottom:20px;">Audit Logs</h2>';
                
                // Filters
                html += '<div style="margin-bottom:20px; display:flex; gap:10px;">';
                html += '<input type="text" id="audit-search" placeholder="Search actions..." style="width:250px; padding:8px 12px; border:1px solid #ddd; border-radius:4px; font-size:14px;">';
                html += '<select id="audit-filter" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; font-size:14px;">';
                html += '<option value="">All actions</option>';
                html += '<option value="offboard">Offboard</option>';
                html += '<option value="disable_users">Disable Users</option>';
                html += '<option value="delete_tokens">Delete Tokens</option>';
                html += '<option value="remote_wipe">Remote Wipe</option>';
                html += '</select>';
                html += '</div>';
                
                // Table
                html += '<table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #ddd; border-radius:8px; overflow:hidden;">';
                html += '<thead><tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">Time</th>';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">Action</th>';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">User</th>';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">Actor</th>';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">Status</th>';
                html += '</tr></thead><tbody>';
                
                for (var i = 0; i < logs.length; i++) {
                    var log = logs[i];
                    var statusColor = log.status === 'success' ? '#4caf50' : '#f44336';
                    var actionColor = '#2196f3';
                    
                    if (log.action === 'disable_users') actionColor = '#f44336';
                    else if (log.action === 'delete_tokens') actionColor = '#ff9800';
                    else if (log.action === 'remote_wipe') actionColor = '#9c27b0';
                    
                    var date = new Date(log.timestamp * 1000).toLocaleString();
                    
                    html += '<tr style="border-bottom:1px solid #eee;" data-action="' + log.action + '" data-user="' + log.userId + '">';
                    html += '<td style="padding:10px 12px; color:#666; font-size:13px;">' + date + '</td>';
                    html += '<td style="padding:10px 12px;"><span style="color:' + actionColor + '; font-weight:500;">' + log.action + '</span></td>';
                    html += '<td style="padding:10px 12px; color:#333;">' + (log.userId || '-') + '</td>';
                    html += '<td style="padding:10px 12px; color:#333;">' + (log.actor || '-') + '</td>';
                    html += '<td style="padding:10px 12px;"><span style="color:' + statusColor + '; font-weight:500;">' + log.status + '</span></td>';
                    html += '</tr>';
                }
                
                if (logs.length === 0) {
                    html += '<tr><td colspan="5" style="padding:20px; text-align:center; color:#888;">No audit logs found</td></tr>';
                }
                
                html += '</tbody></table>';
                html += '<p style="margin-top:10px; color:#888; font-size:14px;">Showing ' + logs.length + ' logs</p>';
                html += '</div>';
                
                mountPoint.innerHTML = html;
                
                // Search
                var searchInput = document.getElementById('audit-search');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        var filter = this.value.toLowerCase();
                        var rows = document.querySelectorAll('tr[data-action]');
                        for (var i = 0; i < rows.length; i++) {
                            var text = rows[i].textContent.toLowerCase();
                            rows[i].style.display = text.indexOf(filter) > -1 ? '' : 'none';
                        }
                    });
                }
                
                // Filter
                var filterSelect = document.getElementById('audit-filter');
                if (filterSelect) {
                    filterSelect.addEventListener('change', function() {
                        var filter = this.value;
                        var rows = document.querySelectorAll('tr[data-action]');
                        for (var i = 0; i < rows.length; i++) {
                            var action = rows[i].getAttribute('data-action');
                            rows[i].style.display = !filter || action === filter ? '' : 'none';
                        }
                    });
                }
            }
        }).catch(function(error) {
            mountPoint.innerHTML = '<div style="padding:20px; color:red;">Error loading audit logs</div>';
        });
    }

    function renderOffboardUsers() {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (!mountPoint) return;

        mountPoint.innerHTML = '<div style="padding:20px; width:100%; box-sizing:border-box;"><h2 style="color:#333;">Offboard Users</h2><p>Loading users...</p></div>';

        apiRequest('/users').then(function(response) {
            if (response.success) {
                var users = response.data.users;
                var html = '<div style="padding:20px; width:100%; box-sizing:border-box;">';
                html += '<h2 style="color:#333; margin-bottom:20px;">Offboard Users</h2>';
                html += '<div style="margin-bottom:20px;">';
                html += '<input type="text" id="user-search" placeholder="Search users..." style="width:300px; padding:8px 12px; border:1px solid #ddd; border-radius:4px; font-size:14px;">';
                html += '</div>';
                html += '<table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #ddd; border-radius:8px; overflow:hidden;">';
                html += '<thead><tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">User ID</th>';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">Status</th>';
                html += '<th style="padding:12px; text-align:left; color:#333; font-weight:600;">Actions</th>';
                html += '</tr></thead><tbody>';
                
                var count = 0;
                for (var userId in users) {
                    var user = users[userId];
                    var status = user.enabled !== false ? 'Enabled' : 'Disabled';
                    var statusColor = user.enabled !== false ? '#4caf50' : '#f44336';
                    
                    html += '<tr style="border-bottom:1px solid #eee;" data-userid="' + userId + '">';
                    html += '<td style="padding:10px 12px; color:#333; font-weight:500;">' + userId + '</td>';
                    html += '<td style="padding:10px 12px;"><span style="color:' + statusColor + '; font-weight:500;">' + status + '</span></td>';
                    html += '<td style="padding:10px 12px;">';
                    html += '<button data-action="disable" data-userid="' + userId + '" style="padding:6px 12px; background:#f44336; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-right:5px; font-size:12px;">Disable</button>';
                    html += '<button data-action="wipe" data-userid="' + userId + '" style="padding:6px 12px; background:#ff9800; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-right:5px; font-size:12px;">Wipe</button>';
                    html += '<button data-action="tokens" data-userid="' + userId + '" style="padding:6px 12px; background:#2196f3; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:12px;">Delete Tokens</button>';
                    html += '</td></tr>';
                    count++;
                }
                
                if (count === 0) {
                    html += '<tr><td colspan="3" style="padding:20px; text-align:center; color:#888;">No users found</td></tr>';
                }
                
                html += '</tbody></table>';
                html += '<p style="margin-top:10px; color:#888; font-size:14px;">Total: ' + count + ' users</p>';
                html += '</div>';
                
                mountPoint.innerHTML = html;
                
                var searchInput = document.getElementById('user-search');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        var filter = this.value.toLowerCase();
                        var rows = document.querySelectorAll('tr[data-userid]');
                        for (var i = 0; i < rows.length; i++) {
                            var userId = rows[i].getAttribute('data-userid').toLowerCase();
                            rows[i].style.display = userId.indexOf(filter) > -1 ? '' : 'none';
                        }
                    });
                }

                // Добавить обработчики для кнопок
                var actionButtons = document.querySelectorAll('button[data-action]');
                for (var j = 0; j < actionButtons.length; j++) {
                    actionButtons[j].addEventListener('click', function(e) {
                        var action = this.getAttribute('data-action');
                        var userId = this.getAttribute('data-userid');
                        
                        if (action === 'disable') {
                            window.disableUser(userId);
                        } else if (action === 'wipe') {
                            window.remoteWipeUser(userId);
                        } else if (action === 'tokens') {
                            window.deleteTokens(userId);
                        }
                    });
                }
            }
        }).catch(function(error) {
            mountPoint.innerHTML = '<div style="padding:20px; color:red;">Error loading users</div>';
        });
    }

    function renderPlaceholder(title) {
        var mountPoint = document.getElementById('adminoffboard-content');
        if (mountPoint) {
            mountPoint.innerHTML = '<div style="padding:20px; width:100%; box-sizing:border-box;"><h2 style="color:#333;">' + title + '</h2></div>';
            setTimeout(fixWidth, 50);
        }
    }

    function init() {
        var path = window.location.hash || '#/dashboard';
        path = path.replace('#', '') || '/dashboard';

        console.log('Rendering path:', path);

        if (path === '/dashboard') {
            renderDashboard();
            setTimeout(fixWidth, 50);
            setTimeout(fixWidth, 200);
        } else if (path === '/offboard') {
            renderOffboardUsers();
        } else if (path === '/bulk') {
            renderBulk();
        } else if (path === '/audit') {
            renderAuditLogs();
        } else if (path === '/queue') {
            renderQueue();
        } else if (path === '/settings') {
            renderSettings();
        } else {
            renderDashboard();
        }

        // Bind navigation
        var links = document.querySelectorAll('.nav-item a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function(e) {
                e.preventDefault();
                var path = this.getAttribute('href').replace('#', '');
                
                if (path === '/dashboard') {
                    renderDashboard();
                    setTimeout(fixWidth, 50);
                } else if (path === '/offboard') {
                    renderOffboardUsers();
                } else if (path === '/bulk') {
                    renderBulk();
                } else if (path === '/audit') {
                    renderAuditLogs();
                } else if (path === '/queue') {
                    renderQueue();
                } else if (path === '/settings') {
                    renderSettings();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
